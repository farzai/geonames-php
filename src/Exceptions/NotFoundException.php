<?php

declare(strict_types=1);

namespace Farzai\Geonames\Exceptions;

/**
 * An `*OrFail` lookup, or an Eloquent cast set with a code that does not exist.
 */
final class NotFoundException extends GeonamesException
{
    public static function for(string $what, string|int $key): self
    {
        return new self(sprintf('No %s found for "%s".', $what, $key));
    }
}
