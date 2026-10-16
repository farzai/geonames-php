<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Closure;

/** Passes when the value is a country code: ISO 3166-1 alpha-2, alpha-3 or numeric. */
final class GeonamesCountry extends BaseRule
{
    protected function check(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_int($value)) {
            $this->message($fail, 'country', $attribute);

            return;
        }
        if ($this->repository()->country($value) === null) {
            $this->message($fail, 'country', $attribute);
        }
    }
}
