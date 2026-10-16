<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Rules;

use Closure;
use Farzai\Geonames\Exceptions\PostalCodesElsewhereException;

/**
 * Passes when the value matches the postal-code pattern of the country in the parent
 * field. A country with no pattern accepts any non-empty code. With `mustExist()` the
 * code must also be in the fetched postal file (spaces and case are ignored): a code in
 * a `partial`-precision country (CL, CN, IE, MT, AR, BR: only the first characters are
 * known) matches when it starts with a listed prefix, never as an exact code. Listed
 * codes zero-padded to full length (CN 230000, BR 69945-000) count without the padding.
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
            $known = $this->listed($country->iso2(), $value);
        } catch (PostalCodesElsewhereException) {
            $this->message($fail, 'postal_elsewhere', $attribute);

            return;
        }
        if (! $known) {
            $this->message($fail, 'postal_code_unknown', $attribute);
        }
    }

    /**
     * Streams the country's postal file and stops at the first match, so a large country
     * is never held in memory.
     */
    private function listed(string $country, string $value): bool
    {
        $wanted = self::squash($value);
        foreach ($this->repository()->rawRows('postal_codes', $country) as $row) {
            $code = self::squash((string) ($row['postal_code'] ?? ''));
            if (($row['precision'] ?? null) === 'full') {
                if ($code === $wanted) {
                    return true;
                }
            } elseif (str_starts_with($wanted, self::prefix($code))) {
                return true;
            }
        }

        return false;
    }

    private static function squash(string $code): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $code));
    }

    /**
     * A partial code is zero-padded to the pattern's length (CN 230000, CL 2340000, BR
     * 69945-000) or already a short prefix (IE F12, MT ATD, AR 3636). The padding is not
     * part of the prefix.
     */
    private static function prefix(string $code): string
    {
        if (preg_match('/^(\d{5}-)0+$/', $code, $m) === 1) {
            return $m[1];
        }
        if (preg_match('/^\d{6,}$/', $code) === 1) {
            while (strlen($code) > 2 && str_ends_with($code, '00')) {
                $code = substr($code, 0, -2);
            }
        }

        return $code;
    }
}
