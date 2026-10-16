<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

use Farzai\Geonames\Exceptions\SnapshotException;

/**
 * What a snapshot holds and where. Paths are the ones in the manifest of schema 1.
 */
final class Datasets
{
    /** Files that exist once per snapshot. */
    public const GLOBAL = [
        'countries' => 'countries.json',
        'kinds' => 'kinds.json',
        'time_zones' => 'time_zones.json',
        'admin1' => 'admin1.json',
    ];

    /** Files that exist once per country. */
    public const PER_COUNTRY = ['admin2', 'cities', 'alternate_names', 'postal_codes'];

    /** What `fetch` gets when no dataset is named. */
    public const DEFAULT = ['admin1', 'admin2', 'cities'];

    public static function country(string $code): string
    {
        $code = strtoupper($code);
        if (preg_match('/^[A-Z]{2}$/', $code) !== 1) {
            throw SnapshotException::invalidCountry($code);
        }

        return $code;
    }

    /**
     * Manifest path of one dataset, for a country when the dataset is per country.
     */
    public static function path(string $dataset, ?string $country = null): string
    {
        if (isset(self::GLOBAL[$dataset])) {
            return self::GLOBAL[$dataset];
        }
        if (in_array($dataset, self::PER_COUNTRY, true)) {
            $country = self::country($country ?? '');

            return sprintf('by-country/%s/%s.ndjson.gz', $country, $dataset);
        }

        throw SnapshotException::invalidDataset($dataset);
    }

    public static function sqlitePath(string $country): string
    {
        return sprintf('sqlite/%s.sqlite', self::country($country));
    }

    public static function isGlobal(string $dataset): bool
    {
        return isset(self::GLOBAL[$dataset]);
    }

    /**
     * A relative path from a manifest that is safe to join to the store directory.
     */
    public static function assertSafePath(string $path): void
    {
        if (preg_match('#^[A-Za-z0-9][A-Za-z0-9._\-]*(/[A-Za-z0-9][A-Za-z0-9._\-]*)*$#', $path) !== 1
            || str_contains($path, '..')) {
            throw SnapshotException::pathEscapes($path);
        }
    }
}
