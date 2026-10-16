<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Illuminate\Support\Arr;

/**
 * Finds the value of a parent field (`country`) for the attribute being validated.
 * Wildcards pair by position: attribute `addresses.2.city` with parent
 * `addresses.*.country` reads `addresses.2.country`.
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
        if (str_contains($field, '*')) {
            preg_match_all('/\.(\d+)(?=\.|$)/', '.'.$attribute, $matches);
            foreach ($matches[1] as $index) {
                $field = (string) preg_replace('/\*/', $index, $field, 1);
            }
        }

        return Arr::get($data, $field);
    }
}
