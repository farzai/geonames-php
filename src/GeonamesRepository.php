<?php

declare(strict_types=1);

namespace Farzai\Geonames;

use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\Exceptions\NotFoundException;
use Farzai\Geonames\Models\Admin1;
use Farzai\Geonames\Models\Admin2;
use Farzai\Geonames\Models\City;
use Farzai\Geonames\Models\Country;
use Farzai\Geonames\Models\Model;
use Farzai\Geonames\Models\PostalCode;
use Farzai\Geonames\Snapshot\Datasets;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Source\ArraySource;
use Farzai\Geonames\Source\Source;
use Farzai\Geonames\Source\StoreSource;
use Illuminate\Support\Collection;

/**
 * Reads the snapshot through a {@see Source}. It never uses the network: data that has
 * not been fetched raises a {@see NotFetchedException} that names the command.
 *
 * Rows with `status: abolished` are hidden unless {@see withAbolished()} is used.
 */
final class GeonamesRepository
{
    private bool $withAbolished = false;

    /** @var array<string, Collection<int, Country>> */
    private array $countryCache = [];

    public function __construct(private readonly Source $source) {}

    public static function default(?SnapshotStore $store = null, ?Pin $pin = null, ?string $snapshot = null): self
    {
        $pin ??= Pin::bundled();

        return new self(new StoreSource($store ?? SnapshotStore::default(), $pin, $snapshot));
    }

    /**
     * A fake for tests. Rows use the snapshot's field names.
     *
     * @param  list<array<string, mixed>>  $countries
     * @param  list<array<string, mixed>>  $admin1
     * @param  array<string, list<array<string, mixed>>>  $admin2  by country code
     * @param  array<string, list<array<string, mixed>>>  $cities  by country code
     * @param  array<string, list<array<string, mixed>>>  $postalCodes  by country code
     */
    public static function fromArrays(array $countries = [], array $admin1 = [], array $admin2 = [], array $cities = [], array $postalCodes = []): self
    {
        $upper = fn (array $byCountry): array => array_change_key_case($byCountry, CASE_UPPER);

        return new self(new ArraySource(
            ['countries' => $countries, 'admin1' => $admin1],
            ['admin2' => $upper($admin2), 'cities' => $upper($cities), 'postal_codes' => $upper($postalCodes)],
        ));
    }

    public function withAbolished(): self
    {
        $copy = clone $this;
        $copy->withAbolished = true;
        $copy->countryCache = [];

        return $copy;
    }

    public function snapshot(): string
    {
        return $this->source->snapshot();
    }

    /**
     * Raw rows of one dataset in the snapshot's own field names, abolished rows included,
     * for importers (the Laravel seeder). `$country` is required for per-country datasets.
     *
     * @return iterable<array<string, mixed>>
     */
    public function rawRows(string $dataset, ?string $country = null): iterable
    {
        return Datasets::isGlobal($dataset) ? $this->source->global($dataset) : $this->source->rows($dataset, (string) $country);
    }

    /**
     * Countries whose file for a per-country dataset has been fetched.
     *
     * @return list<string>
     */
    public function fetched(string $dataset): array
    {
        return $this->source->available($dataset);
    }

    /**
     * @return Collection<int, Country>
     */
    public function countries(): Collection
    {
        return $this->countryCache['all'] ??= $this->models('countries', Country::class, $this->source->global('countries'));
    }

    /**
     * By ISO 3166-1 alpha-2, alpha-3 or numeric code (any case, numeric with or without leading zeros).
     */
    public function country(string|int $code): ?Country
    {
        $code = strtoupper(trim((string) $code));
        $numeric = ctype_digit($code) ? str_pad($code, 3, '0', STR_PAD_LEFT) : null;

        return $this->countries()->first(fn (Country $c): bool => $c->iso2() === $code
            || $c->iso3() === $code
            || ($numeric !== null && $c->isoNumeric() === $numeric));
    }

    public function countryOrFail(string|int $code): Country
    {
        return $this->country($code) ?? throw NotFoundException::for('country', $code);
    }

    /**
     * The published feature-code to kind table.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function kinds(): Collection
    {
        return collect($this->source->global('kinds'));
    }

    /**
     * IANA time zone names, for one country or for all.
     *
     * @return list<string>
     */
    public function timeZones(?string $country = null): array
    {
        $country = $country === null ? null : strtoupper($country);

        return collect($this->source->global('time_zones'))
            ->filter(fn (array $row): bool => $country === null || ($row['country_code'] ?? null) === $country)
            ->pluck('time_zone')->filter()->unique()->sort()->values()->all();
    }

    /**
     * @return Collection<int, Admin1>
     */
    public function admin1(string $country): Collection
    {
        $country = Datasets::country($country);

        return $this->models('admin1', Admin1::class, array_filter(
            $this->source->global('admin1'),
            fn (array $row): bool => ($row['country_code'] ?? null) === $country
        ));
    }

    /**
     * One first-level division by its country and GeoNames admin1 code (`TH`, `50`).
     */
    public function admin1Code(string $country, string $code): ?Admin1
    {
        return $this->admin1($country)->first(fn (Admin1 $a): bool => $a->admin1Code() === $code);
    }

    /**
     * @return Collection<int, Admin2>
     */
    public function admin2(string $country, ?string $admin1 = null): Collection
    {
        return $this->models('admin2', Admin2::class, $this->source->rows('admin2', $country), fn (array $row): bool => $admin1 === null || ($row['admin1_code'] ?? null) === $admin1);
    }

    /**
     * Cities of one country: every row has a population of 500 or more, or is an
     * administrative seat.
     *
     * @return Collection<int, City>
     */
    public function cities(string $country, ?string $admin1 = null, ?int $minPopulation = null): Collection
    {
        return $this->models('cities', City::class, $this->source->rows('cities', $country), fn (array $row): bool => ($admin1 === null || ($row['admin1_code'] ?? null) === $admin1)
            && ($minPopulation === null || (int) ($row['population'] ?? 0) >= $minPopulation));
    }

    /**
     * A city by `geoname_id`. The lookup reads fetched country files only: with a
     * country it reads that one; without, every country that has been fetched. It never
     * downloads, and raises {@see NotFetchedException} when nothing was fetched.
     */
    public function city(int $id, ?string $country = null): ?City
    {
        $countries = $country === null ? $this->source->available('cities') : [Datasets::country($country)];
        if ($countries === []) {
            throw NotFetchedException::country('any country', ['cities'], $this->source->snapshot());
        }

        foreach ($countries as $code) {
            foreach ($this->source->rows('cities', $code) as $row) {
                if ((int) ($row['geoname_id'] ?? 0) === $id) {
                    $city = new City($row);

                    return $this->visible($city) ? $city : null;
                }
            }
        }

        return null;
    }

    /**
     * Postal rows of a country, or only those of one code. A code can have several rows
     * (one per place). Thailand has none here and says where they are.
     *
     * @return Collection<int, PostalCode>
     *
     * @throws Exceptions\PostalCodesElsewhereException for Thailand
     */
    public function postalCodes(string $country, ?string $code = null): Collection
    {
        $code = $code === null ? null : strtoupper(trim($code));

        return $this->models('postal_codes', PostalCode::class, $this->source->rows('postal_codes', $country), fn (array $row): bool => $code === null || strtoupper((string) ($row['postal_code'] ?? '')) === $code);
    }

    /**
     * Cities by name in any selected language, prefix matches first. Without a country
     * it looks through the countries that have been fetched.
     *
     * @return Collection<int, City>
     */
    public function search(string $term, ?string $country = null, int $limit = 20): Collection
    {
        $needle = mb_strtolower(trim($term));
        if ($needle === '') {
            return new Collection;
        }

        $countries = $country === null ? $this->source->available('cities') : [Datasets::country($country)];
        $ranked = [];
        foreach ($countries as $code) {
            foreach ($this->source->rows('cities', $code) as $row) {
                $rank = $this->rank($row, $needle);
                if ($rank !== null) {
                    $city = new City($row);
                    if ($this->visible($city)) {
                        $ranked[] = [$rank, -((int) ($row['population'] ?? 0)), $city];
                    }
                }
            }
        }
        usort($ranked, fn (array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return collect(array_slice(array_column($ranked, 2), 0, $limit));
    }

    /**
     * The closest cities to a point in one country, nearest first. It reads the
     * country's SQLite file (fetch it with `--sqlite`), so it needs `pdo_sqlite` and
     * raises {@see NotFetchedException} without the file. Each result carries
     * `distance_km` in `toArray()`.
     *
     * @return Collection<int, City>
     */
    public function nearest(float $lat, float $lon, string $country, int $limit = 5): Collection
    {
        return collect($this->source->nearest(Datasets::country($country), $lat, $lon, max(1, $limit)))
            ->map(fn (array $row): City => new City($row))
            ->filter(fn (City $c): bool => $this->visible($c))
            ->values();
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @param  iterable<array<string, mixed>>  $rows
     * @param  (\Closure(array<string, mixed>): bool)|null  $filter
     * @return Collection<int, T>
     */
    private function models(string $dataset, string $class, iterable $rows, ?\Closure $filter = null): Collection
    {
        $out = [];
        foreach ($rows as $row) {
            if ($filter !== null && ! $filter($row)) {
                continue;
            }
            $model = new $class($row);
            if ($this->visible($model)) {
                $out[] = $model;
            }
        }

        return new Collection($out);
    }

    private function visible(Model $model): bool
    {
        return $this->withAbolished || ! $model->isAbolished();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rank(array $row, string $needle): ?int
    {
        $names = array_filter([
            (string) ($row['name'] ?? ''), (string) ($row['ascii_name'] ?? ''),
            ...array_values(array_filter((array) ($row['names'] ?? []), 'is_string')),
        ], fn (string $n): bool => $n !== '');
        $best = null;
        foreach ($names as $name) {
            $name = mb_strtolower($name);
            $rank = $name === $needle ? 0 : (str_starts_with($name, $needle) ? 1 : (str_contains($name, $needle) ? 2 : null));
            if ($rank !== null && ($best === null || $rank < $best)) {
                $best = $rank;
            }
        }

        return $best;
    }
}
