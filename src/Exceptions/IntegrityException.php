<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

/**
 * A downloaded file does not match the sha256 or size the manifest promises, or a
 * snapshot is not the one that was pinned. Nothing is kept on disk when this is
 * thrown.
 */
final class IntegrityException extends GeonamesException
{
    public static function checksum(string $path, string $expected, string $actual): self
    {
        return new self(sprintf('%s does not match the manifest: expected sha256 %s, got %s.', $path, $expected, $actual));
    }

    public static function size(string $path, int $expected, int $actual): self
    {
        return new self(sprintf('%s does not match the manifest: expected %d bytes, got %d.', $path, $expected, $actual));
    }
}
