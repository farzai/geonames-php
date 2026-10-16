<?php

declare(strict_types=1);

namespace Farzai\Geonames\Source;

use Farzai\Geonames\Data\Reader;
use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\Exceptions\PostalCodesElsewhereException;
use Farzai\Geonames\Snapshot\Datasets;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotStore;

/**
 * Reads the snapshot in the local store, never the network. countries, kinds and
 * time_zones are bundled with the package for the pinned snapshot, so the store need
 * not hold them; everything else must have been fetched.
 */
final class StoreSource implements Source
{
    private readonly string $snapshot;

    /** @var array<string, list<array<string, mixed>>> */
    private array $cache = [];

    public function __construct(
        private readonly SnapshotStore $store,
        private readonly Pin $pin,
        ?string $snapshot = null,
        private readonly ?string $bundledDirectory = null,
    ) {
        $this->snapshot = $snapshot ?? $pin->snapshot;
    }

    public function snapshot(): string
    {
        return $this->snapshot;
    }

    public function global(string $dataset): array
    {
        if (isset($this->cache[$dataset])) {
            return $this->cache[$dataset];
        }

        $path = Datasets::path($dataset);
        if ($this->store->has($this->snapshot, $path)) {
            return $this->cache[$dataset] = Reader::envelope($this->store->path($this->snapshot, $path));
        }

        $bundled = ($this->bundledDirectory ?? dirname(__DIR__, 2).'/data').'/'.$path;
        if ($this->snapshot === $this->pin->snapshot && in_array($dataset, ['countries', 'kinds', 'time_zones'], true) && is_file($bundled)) {
            return $this->cache[$dataset] = Reader::envelope($bundled);
        }

        throw NotFetchedException::global($dataset, $this->snapshot);
    }

    public function rows(string $dataset, string $country): iterable
    {
        $country = Datasets::country($country);
        $path = Datasets::path($dataset, $country);

        if (! $this->store->has($this->snapshot, $path)) {
            if ($dataset === 'postal_codes' && $country === 'TH') {
                throw PostalCodesElsewhereException::thailand();
            }

            throw NotFetchedException::country($country, [$dataset], $this->snapshot);
        }

        return Reader::ndjson($this->store->path($this->snapshot, $path));
    }

    public function available(string $dataset): array
    {
        $directory = $this->store->snapshotDir($this->snapshot).'/by-country';
        $countries = [];
        foreach (glob($directory.'/*/'.$dataset.'.ndjson.gz') ?: [] as $file) {
            $countries[] = basename(dirname($file));
        }
        sort($countries);

        return $countries;
    }

    public function nearest(string $country, float $lat, float $lon, int $limit): array
    {
        $path = Datasets::sqlitePath($country);
        if (! $this->store->has($this->snapshot, $path)) {
            throw NotFetchedException::sqlite(Datasets::country($country), $this->snapshot);
        }

        return SqliteNearest::find($this->store->path($this->snapshot, $path), $lat, $lon, $limit);
    }
}
