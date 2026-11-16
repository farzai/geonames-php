<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Illuminate\Support\Arr;

/**
 * Finds the value of a parent field (`country`) for the attribute being validated.
 * Each wildcard takes the attribute's key at the same depth: attribute
 * `items.0.addresses.2.city` with parent `items.0.addresses.*.country` reads
 * `items.0.addresses.2.country`.
 */
final class ParentField
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function value(string $attribute, ?string $field, array $data): mixed
    {
        if ($field === null || $field === '') {
            return null;
        }
        $keys = explode('.', $attribute);
        $segments = explode('.', $field);
        foreach ($segments as $depth => $segment) {
            if ($segment === '*' && isset($keys[$depth])) {
                $segments[$depth] = $keys[$depth];
            }
        }

        return Arr::get($data, implode('.', $segments));
    }
}
