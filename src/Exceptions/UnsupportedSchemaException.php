<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

/**
 * A snapshot with a different schema major version than this package reads. A newer
 * minor version is read as usual: unknown fields are ignored.
 */
final class UnsupportedSchemaException extends GeonamesException
{
    public static function major(string $found, int $supported): self
    {
        return new self(sprintf(
            'Schema version %s is not supported: this package reads schema major %d. Update farzai/geonames.',
            $found,
            $supported,
        ));
    }
}
