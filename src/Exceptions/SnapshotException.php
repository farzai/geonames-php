<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

/**
 * The snapshot cannot be used: it is withdrawn, unknown, or the download failed.
 */
final class SnapshotException extends GeonamesException
{
    public static function withdrawn(string $snapshot, string $status): self
    {
        return new self(sprintf('Snapshot %s is %s and is refused.', $snapshot, $status));
    }

    public static function unknown(string $snapshot): self
    {
        return new self(sprintf('Snapshot %s is not listed in snapshots.json.', $snapshot));
    }

    public static function download(string $url, string $reason): self
    {
        return new self(sprintf('Could not download %s: %s', $url, $reason));
    }

    public static function invalidCountry(string $code): self
    {
        return new self(sprintf('"%s" is not an ISO 3166-1 alpha-2 country code (two letters, A to Z).', $code));
    }

    public static function invalidDataset(string $dataset): self
    {
        return new self(sprintf('"%s" is not a dataset this package can fetch.', $dataset));
    }

    public static function pathEscapes(string $path): self
    {
        return new self(sprintf('The manifest lists "%s", which is not a safe relative path; refusing it.', $path));
    }
}
