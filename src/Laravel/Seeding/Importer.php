<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Seeding;

use Farzai\Geonames\GeonamesRepository;
use Illuminate\Database\ConnectionInterface;

/**
 * Copies fetched snapshot data into the tables of the publishable migration, streaming
 * and in chunks. Re-running is safe: rows with a geoname_id are upserted on it; postal
 * codes and alternate names (which have no id of their own) are replaced per country
 * inside one transaction, so a code removed from a later snapshot goes too. Postal rows
 * that repeat in every column are deleted after the insert, keeping the first, and counted
 * in `$duplicatesDropped`.
 * Abolished rows are kept with their status.
 */
final class Importer
{
    private const CHUNK = 1000;

    /** Postal codes looked at per round trip when removing repeated rows. */
    private const GROUP_PAGE = 500;

    /** Bind parameters allowed in one statement; the lowest limit among the supported databases (SQL Server: 2100). */
    private const MAX_BINDINGS = 2000;

    /** Identical postal rows deleted after the insert, over all imports of this instance. */
    public int $duplicatesDropped = 0;

    /**
     * @param  array<string, string>  $tables
     */
    public function __construct(
        private readonly GeonamesRepository $repository,
        private readonly ConnectionInterface $db,
        private readonly array $tables,
    ) {}

    /**
     * @param  list<string>  $countries  empty = every country whose cities file was fetched
     * @param  list<string>  $datasets  countries, admin1, admin2, cities, postal_codes, alternate_names
     * @return array<string, int> rows written per dataset
     */
    public function import(array $countries = [], array $datasets = ['countries', 'admin1', 'admin2', 'cities']): array
    {
        $countries = array_map('strtoupper', $countries);
        $counts = [];

        foreach ($datasets as $dataset) {
            $counts[$dataset] = match ($dataset) {
                'countries' => $this->countries($countries),
                'admin1' => $this->admin1($countries),
                default => $this->perCountry($dataset, $countries ?: $this->repository->fetched($dataset)),
            };
        }

        return $counts;
    }

    /**
     * @param  list<string>  $countries
     */
    private function countries(array $countries): int
    {
        $rows = [];
        foreach ($this->repository->rawRows('countries') as $row) {
            if ($countries !== [] && ! in_array($row['iso2'] ?? '', $countries, true)) {
                continue;
            }
            $rows[] = [
                'geoname_id' => $row['geoname_id'], 'iso2' => $row['iso2'], 'iso3' => $row['iso3'] ?? '', 'iso_numeric' => $row['iso_numeric'] ?? null,
                'name' => $row['name'], 'names' => json_encode($row['names'] ?? new \stdClass, JSON_UNESCAPED_UNICODE), 'capital' => $row['capital'] ?? null,
                'continent' => $row['continent'] ?? null, 'population' => $row['population'] ?? null,
                'currency_code' => $row['currency']['code'] ?? null, 'calling_code' => $row['calling_code'] ?? null,
                'languages' => json_encode($row['languages'] ?? []), 'time_zones' => json_encode($row['time_zones'] ?? []),
                'postal_code_regex' => $row['postal_code_regex'] ?? null, 'wikidata_id' => $row['wikidata_id'] ?? null, 'status' => $row['status'] ?? 'active',
            ];
        }

        return $this->upsert('countries', $rows, ['geoname_id']);
    }

    /**
     * @param  list<string>  $countries
     */
    private function admin1(array $countries): int
    {
        $written = 0;
        $buffer = [];
        foreach ($this->repository->rawRows('admin1') as $row) {
            if ($countries !== [] && ! in_array($row['country_code'] ?? '', $countries, true)) {
                continue;
            }
            $buffer[] = [
                'geoname_id' => $row['geoname_id'], 'geonames_code' => $row['geonames_code'], 'country_code' => $row['country_code'], 'admin1_code' => $row['admin1_code'],
                'name' => $row['name'], 'ascii_name' => $row['ascii_name'], 'names' => json_encode($row['names'] ?? new \stdClass, JSON_UNESCAPED_UNICODE),
                'iso_3166_2' => $row['iso_3166_2'] ?? null, 'wikidata_id' => $row['wikidata_id'] ?? null, 'status' => $row['status'] ?? 'active',
            ];
            if (count($buffer) >= self::CHUNK) {
                $written += $this->upsert('admin1', $buffer, ['geoname_id']);
                $buffer = [];
            }
        }

        return $written + $this->upsert('admin1', $buffer, ['geoname_id']);
    }

    /**
     * @param  list<string>  $countries
     */
    private function perCountry(string $dataset, array $countries): int
    {
        $written = 0;
        foreach ($countries as $country) {
            if ($dataset === 'alternate_names') {
                $written += $this->alternateNames($country);

                continue;
            }
            if ($dataset === 'postal_codes') {
                $written += $this->postalCodes($country);

                continue;
            }
            $buffer = [];
            foreach ($this->repository->rawRows($dataset, $country) as $row) {
                $buffer[] = match ($dataset) {
                    'admin2' => $this->admin2Row($row),
                    'cities' => $this->cityRow($row),
                    default => throw new \InvalidArgumentException(sprintf('Cannot import "%s".', $dataset)),
                };
                if (count($buffer) >= self::CHUNK) {
                    $written += $this->flush($dataset, $buffer);
                    $buffer = [];
                }
            }
            $written += $this->flush($dataset, $buffer);
        }

        return $written;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function flush(string $dataset, array $rows): int
    {
        return $this->upsert($dataset, $rows, ['geoname_id']);
    }

    private function postalCodes(string $country): int
    {
        $table = $this->tables['postal_codes'];
        $written = 0;

        $this->db->transaction(function () use ($country, $table, &$written): void {
            $this->db->table($table)->where('country_code', $country)->delete();
            $buffer = [];
            foreach ($this->repository->rawRows('postal_codes', $country) as $row) {
                $buffer[] = $this->postalRow($row);
                if (count($buffer) >= self::CHUNK) {
                    $written += $this->insert($table, $buffer);
                    $buffer = [];
                }
            }
            $written += $this->insert($table, $buffer);
            $dropped = $this->dropRepeatedRows($table, $country);
            $written -= $dropped;
            $this->duplicatesDropped += $dropped;
        });

        return $written;
    }

    /**
     * Keeps the first of each group of rows that are equal in every value column. Memory
     * stays flat (GB has 1.8 million rows): the postal codes that occur more than once
     * come from an index-only GROUP BY, a page at a time; only the rows of those codes are
     * read back, compared in PHP, and the repeats deleted by id. No window function and no
     * subquery on the table being deleted from: no per-row subplan and no window sort, which
     * is what a ROW_NUMBER delete costs (Postgres chose a nested loop for it when the
     * country's rows were new and unanalysed), and it runs on any supported database. The
     * planner still chooses between a hash and a stream aggregate for the GROUP BY by what
     * it knows of the table. Under a hash aggregate every page re-reads the rest of the
     * country's rows (postal_code > the last code of the previous page), so the cost grows
     * with pages times the country's rows, not with one code's rows; that is fine at the
     * measured sizes. "Index-only" holds once a vacuum has set the visibility map. The
     * comparison is exact, so a collation that ignores case or accents cannot merge two rows.
     */
    private function dropRepeatedRows(string $table, string $country): int
    {
        $columns = array_keys($this->postalRow(['country_code' => $country, 'postal_code' => '', 'place_name' => '']));
        $dropped = 0;
        $after = null;

        while (true) {
            $codes = $this->db->table($table)->where('country_code', $country)
                ->when($after !== null, fn ($query) => $query->where('postal_code', '>', $after))
                ->groupBy('postal_code')->havingRaw('count(*) > 1')
                ->orderBy('postal_code')->limit(self::GROUP_PAGE)
                ->pluck('postal_code')->all();
            if ($codes === []) {
                return $dropped;
            }
            $after = end($codes);

            $seen = [];
            $repeats = [];
            $rows = $this->db->table($table)->where('country_code', $country)->whereIn('postal_code', $codes)
                ->orderBy('id')->get(array_merge(['id'], $columns));
            foreach ($rows as $row) {
                $values = (array) $row;
                $id = $values['id'];
                unset($values['id']);
                $key = md5(json_encode(array_values($values), JSON_THROW_ON_ERROR));
                if (isset($seen[$key])) {
                    $repeats[] = $id;
                } else {
                    $seen[$key] = true;
                }
            }
            foreach (array_chunk($repeats, self::MAX_BINDINGS) as $ids) {
                $dropped += $this->db->table($table)->whereIn('id', $ids)->delete();
            }
        }
    }

    private function alternateNames(string $country): int
    {
        $table = $this->tables['alternate_names'];
        $written = 0;

        $this->db->transaction(function () use ($country, $table, &$written): void {
            $this->db->table($table)->where('country_code', $country)->delete();
            $buffer = [];
            foreach ($this->repository->rawRows('alternate_names', $country) as $row) {
                $buffer[] = [
                    'country_code' => $country, 'geoname_id' => $row['geoname_id'], 'lang' => (string) $row['lang'], 'name' => $row['name'],
                    'preferred' => (int) (bool) ($row['preferred'] ?? false), 'short' => (int) (bool) ($row['short'] ?? false),
                    'colloquial' => (int) (bool) ($row['colloquial'] ?? false), 'historic' => (int) (bool) ($row['historic'] ?? false),
                ];
                if (count($buffer) >= self::CHUNK) {
                    $written += $this->insert($table, $buffer);
                    $buffer = [];
                }
            }
            $written += $this->insert($table, $buffer);
        });

        return $written;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function admin2Row(array $row): array
    {
        return [
            'geoname_id' => $row['geoname_id'], 'geonames_code' => $row['geonames_code'], 'country_code' => $row['country_code'], 'admin1_code' => $row['admin1_code'],
            'admin2_code' => $row['admin2_code'], 'admin1_geoname_id' => $row['admin1_geoname_id'] ?? null, 'name' => $row['name'], 'ascii_name' => $row['ascii_name'],
            'names' => json_encode($row['names'] ?? new \stdClass, JSON_UNESCAPED_UNICODE), 'iso_3166_2' => $row['iso_3166_2'] ?? null,
            'wikidata_id' => $row['wikidata_id'] ?? null, 'status' => $row['status'] ?? 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function cityRow(array $row): array
    {
        return [
            'geoname_id' => $row['geoname_id'], 'country_code' => $row['country_code'], 'name' => $row['name'], 'ascii_name' => $row['ascii_name'],
            'names' => json_encode($row['names'] ?? new \stdClass, JSON_UNESCAPED_UNICODE), 'lat' => $row['lat'], 'lon' => $row['lon'], 'kind' => $row['kind'] ?? 'other',
            'feature_class' => $row['feature_class'], 'feature_code' => $row['feature_code'], 'is_capital' => (int) (bool) ($row['is_capital'] ?? false),
            'admin1_code' => $row['admin1_code'] ?? null, 'admin2_code' => $row['admin2_code'] ?? null, 'admin1_geoname_id' => $row['admin1_geoname_id'] ?? null,
            'admin2_geoname_id' => $row['admin2_geoname_id'] ?? null, 'population' => $row['population'] ?? 0, 'population_tier' => $row['population_tier'] ?? null,
            'time_zone' => $row['time_zone'] ?? null, 'wikidata_id' => $row['wikidata_id'] ?? null, 'status' => $row['status'] ?? 'active',
            'source_modified' => $row['source']['modified'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function postalRow(array $row): array
    {
        return [
            'country_code' => $row['country_code'], 'postal_code' => (string) $row['postal_code'], 'place_name' => (string) $row['place_name'],
            'admin1_code' => (string) ($row['admin1_code'] ?? ''), 'admin2_code' => (string) ($row['admin2_code'] ?? ''), 'admin3_code' => (string) ($row['admin3_code'] ?? ''),
            'admin1_name' => $row['admin1_name'] ?? null, 'admin2_name' => $row['admin2_name'] ?? null, 'admin3_name' => $row['admin3_name'] ?? null,
            'lat' => $row['lat'] ?? null, 'lon' => $row['lon'] ?? null, 'accuracy' => $row['accuracy'] ?? null,
            'precision' => ($row['precision'] ?? null) === 'full' ? 'full' : 'partial', 'source' => (string) ($row['source'] ?? 'geonames'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $keys
     */
    private function upsert(string $dataset, array $rows, array $keys): int
    {
        if ($rows === []) {
            return 0;
        }
        $update = array_values(array_diff(array_keys($rows[0]), $keys));
        foreach (array_chunk($rows, $this->rowsPerStatement($rows)) as $chunk) {
            $this->db->table($this->tables[$dataset])->upsert($chunk, $keys, $update);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(string $table, array $rows): int
    {
        if ($rows === []) {
            return 0;
        }
        foreach (array_chunk($rows, $this->rowsPerStatement($rows)) as $chunk) {
            $this->db->table($table)->insert($chunk);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function rowsPerStatement(array $rows): int
    {
        return max(1, intdiv(self::MAX_BINDINGS, count($rows[0])));
    }
}
