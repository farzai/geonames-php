<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

/**
 * The data a lookup needs is not in the local snapshot store. Reading never uses
 * the network; the message names the command that downloads the data.
 */
final class NotFetchedException extends GeonamesException
{
    /**
     * @param  list<string>  $datasets
     */
    public static function country(string $country, array $datasets, string $snapshot): self
    {
        return new self(sprintf(
            'The %s data for %s is not in the local store (snapshot %s). Download it with: '
            .'geonames fetch %s --datasets=%s  (Laravel: php artisan geonames:fetch %s --datasets=%s)',
            implode(', ', $datasets),
            $country,
            $snapshot,
            $country,
            implode(',', $datasets),
            $country,
            implode(',', $datasets),
        ));
    }

    public static function global(string $dataset, string $snapshot): self
    {
        return new self(sprintf(
            'The %s data is not in the local store (snapshot %s). Download it with: geonames fetch --datasets=%s',
            $dataset,
            $snapshot,
            $dataset,
        ));
    }

    public static function sqlite(string $country, string $snapshot): self
    {
        return new self(sprintf(
            'The SQLite file for %s is not in the local store (snapshot %s). Download it with: geonames fetch %s --sqlite',
            $country,
            $snapshot,
            $country,
        ));
    }
}
