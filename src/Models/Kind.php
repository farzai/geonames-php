<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

/**
 * The `kind` values of schema 1. A value this package does not know (a later schema
 * minor may add one) reads as `other`, as the data rules require.
 */
final class Kind
{
    public const KNOWN = ['country', 'admin1', 'admin2', 'admin3', 'admin4', 'city', 'capital', 'seat', 'locality', 'other'];

    public static function from(mixed $value): string
    {
        return is_string($value) && in_array($value, self::KNOWN, true) ? $value : 'other';
    }
}
