<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Closure;

/**
 * Passes when the value is a GeoNames admin1 code (`50`) of the country in the parent
 * field. Without a parent field the value must be a full `TH.50` code. Needs the admin1
 * data to have been fetched.
 */
final class GeonamesAdmin1 extends BaseRule
{
    protected function check(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_int($value)) {
            $this->message($fail, 'admin1', $attribute);

            return;
        }
        $value = (string) $value;

        if ($this->countryField === null) {
            [$country, $code] = array_pad(explode('.', $value, 2), 2, '');
        } else {
            $country = $this->parentCountry($attribute)?->iso2();
            if ($country === null) {
                return; // the parent's own rule reports it
            }
            $code = $value;
        }

        $found = preg_match('/^[A-Za-z]{2}$/', $country) === 1 && $this->repository()->admin1Code(strtoupper($country), $code) !== null;
        if (! $found) {
            $this->message($fail, 'admin1', $attribute);
        }
    }
}
