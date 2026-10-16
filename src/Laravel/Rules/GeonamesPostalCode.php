<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Closure;
use Farzai\Geonames\Exceptions\PostalCodesElsewhereException;

/**
 * Passes when the value matches the postal-code pattern of the country in the parent
 * field. A country with no pattern accepts any non-empty code. With `mustExist()` the
 * code must also be in the fetched postal file: a code in a `partial`-precision country
 * (CL, CN, IE, MT, AR, BR: only the first characters are known) matches when it starts
 * with a listed prefix, never as an exact code.
 */
final class GeonamesPostalCode extends BaseRule
{
    private bool $mustExist = false;

    public function mustExist(bool $mustExist = true): static
    {
        $this->mustExist = $mustExist;

        return $this;
    }

    protected function check(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_int($value)) {
            $this->message($fail, 'postal_code', $attribute);

            return;
        }
        $value = trim((string) $value);

        $country = $this->parentCountry($attribute);
        if ($country === null) {
            return; // the country's own rule reports it
        }

        $pattern = $country->postalCodeRegex();
        if ($value === '' || ($pattern !== null && preg_match('#'.str_replace('#', '\#', $pattern).'#u', $value) !== 1)) {
            $this->message($fail, 'postal_code', $attribute);

            return;
        }

        if (! $this->mustExist) {
            return;
        }

        try {
            $rows = $this->repository()->postalCodes($country->iso2());
        } catch (PostalCodesElsewhereException) {
            $this->message($fail, 'postal_elsewhere', $attribute);

            return;
        }

        $known = $rows->contains(function ($row) use ($value): bool {
            $code = strtoupper((string) $row->postalCode());

            return $row->isFull()
                ? $code === strtoupper($value)
                : str_starts_with(strtoupper($value), $code);
        });
        if (! $known) {
            $this->message($fail, 'postal_code_unknown', $attribute);
        }
    }
}
