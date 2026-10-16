<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Seeding;

use Farzai\Geonames\GeonamesRepository;
use Illuminate\Database\ConnectionInterface;

/**
 * Copies fetched snapshot data into the tables of the publishable migration, streaming
 * and in chunks. Re-running is safe: rows with a geoname_id are upserted on it, postal
 * rows on the place and its admin codes (nulls stored as ''), and alternate names (which
 * have no id of their own) are replaced per country inside one transaction. Abolished
 * rows are kept with their status.
 */
final class Importer
{
    private const CHUNK = 1000;

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
            $buffer = [];
            foreach ($this->repository->rawRows($dataset, $country) as $row) {
                $buffer[] = match ($dataset) {
                    'admin2' => $this->admin2Row($row),
                    'cities' => $this->cityRow($row),
                    'postal_codes' => $this->postalRow($row),
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
        return $dataset === 'postal_codes'
            ? $this->upsert($dataset, $rows, ['country_code', 'postal_code', 'place_name', 'admin1_code', 'admin2_code', 'admin3_code'])
            : $this->upsert($dataset, $rows, ['geoname_id']);
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
                    $this->db->table($table)->insert($buffer);
                    $written += count($buffer);
                    $buffer = [];
                }
            }
            if ($buffer !== []) {
                $this->db->table($table)->insert($buffer);
                $written += count($buffer);
            }
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
        $this->db->table($this->tables[$dataset])->upsert($rows, $keys, $update);

        return count($rows);
    }
}
