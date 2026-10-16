<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Closure;

/**
 * Passes when the value is the `geoname_id` of a city, in the country of the parent
 * field when one is given. Looks through the countries that were fetched only.
 */
final class GeonamesCity extends BaseRule
{
    protected function check(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            $this->message($fail, 'city', $attribute);

            return;
        }

        $country = null;
        if ($this->countryField !== null) {
            $country = $this->parentCountry($attribute)?->iso2();
            if ($country === null) {
                return;
            }
        }

        if ($this->repository()->city((int) $value, $country) === null) {
            $this->message($fail, 'city', $attribute);
        }
    }
}
