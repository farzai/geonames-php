<?php

declare(strict_types=1);

namespace Farzai\Geonames\Source;

use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\Exceptions\PostalCodesElsewhereException;

/**
 * Where the repository gets rows from. Two real implementations: {@see StoreSource}
 * (the verified local files) and {@see ArraySource} (a fake for tests).
 */
interface Source
{
    /** The snapshot the rows belong to. */
    public function snapshot(): string;

    /**
     * A global dataset: countries, kinds, time_zones or admin1.
     *
     * @return list<array<string, mixed>>
     *
     * @throws NotFetchedException
     */
    public function global(string $dataset): array;

    /**
     * A per-country dataset: admin2, cities, alternate_names or postal_codes.
     *
     * @return iterable<array<string, mixed>>
     *
     * @throws NotFetchedException
     * @throws PostalCodesElsewhereException
     */
    public function rows(string $dataset, string $country): iterable;

    /**
     * Countries whose file for `$dataset` is present, for lookups that name no country.
     *
     * @return list<string>
     */
    public function available(string $dataset): array;

    /**
     * The cities closest to a point in one country, nearest first, each with its
     * distance in kilometres under `distance_km`.
     *
     * @return list<array<string, mixed>>
     *
     * @throws NotFetchedException when the country's SQLite file is not fetched
     */
    public function nearest(string $country, float $lat, float $lon, int $limit): array;
}
