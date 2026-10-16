<?php

declare(strict_types=1);

namespace Farzai\Geonames\Source;

use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\Exceptions\PostalCodesElsewhereException;

/**
 * In-memory rows, for tests and for code that wants a small fake of the data
 * (`Geonames::swap(GeonamesRepository::fromArrays(...))`).
 */
final class ArraySource implements Source
{
    /**
     * @param  array<string, list<array<string, mixed>>>  $global  dataset => rows
     * @param  array<string, array<string, list<array<string, mixed>>>>  $perCountry  dataset => country => rows
     */
    public function __construct(private readonly array $global = [], private readonly array $perCountry = [], private readonly string $snapshot = 'array') {}

    public function snapshot(): string
    {
        return $this->snapshot;
    }

    public function global(string $dataset): array
    {
        return $this->global[$dataset] ?? throw NotFetchedException::global($dataset, $this->snapshot);
    }

    public function rows(string $dataset, string $country): iterable
    {
        $country = strtoupper($country);
        if (isset($this->perCountry[$dataset][$country])) {
            return $this->perCountry[$dataset][$country];
        }
        if ($dataset === 'postal_codes' && $country === 'TH') {
            throw PostalCodesElsewhereException::thailand();
        }

        throw NotFetchedException::country($country, [$dataset], $this->snapshot);
    }

    public function available(string $dataset): array
    {
        $countries = array_keys($this->perCountry[$dataset] ?? []);
        sort($countries);

        return $countries;
    }

    public function nearest(string $country, float $lat, float $lon, int $limit): array
    {
        $country = strtoupper($country);
        if (! isset($this->perCountry['cities'][$country])) {
            throw NotFetchedException::sqlite($country, $this->snapshot);
        }

        $rows = [];
        foreach ($this->perCountry['cities'][$country] as $row) {
            if (($row['status'] ?? 'active') === 'active' && isset($row['lat'], $row['lon'])) {
                $row['distance_km'] = round(Distance::km($lat, $lon, (float) $row['lat'], (float) $row['lon']), 3);
                $rows[] = $row;
            }
        }
        usort($rows, fn (array $a, array $b): int => $a['distance_km'] <=> $b['distance_km']);

        return array_slice($rows, 0, $limit);
    }
}
