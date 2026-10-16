<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

final class Country extends Model
{
    use Named;

    public function iso2(): string
    {
        return (string) $this->string('iso2');
    }

    public function iso3(): ?string
    {
        return $this->string('iso3');
    }

    public function isoNumeric(): ?string
    {
        return $this->string('iso_numeric');
    }

    public function capital(): ?string
    {
        return $this->string('capital');
    }

    public function continent(): ?string
    {
        return $this->string('continent');
    }

    public function population(): ?int
    {
        return $this->int('population');
    }

    public function areaSqKm(): ?float
    {
        return $this->float('area_sq_km');
    }

    /**
     * @return array{code: string, name: ?string}|null
     */
    public function currency(): ?array
    {
        $currency = $this->row['currency'] ?? null;

        return is_array($currency) && isset($currency['code'])
            ? ['code' => (string) $currency['code'], 'name' => isset($currency['name']) ? (string) $currency['name'] : null]
            : null;
    }

    public function callingCode(): ?string
    {
        return $this->string('calling_code');
    }

    /**
     * @return list<string>
     */
    public function timeZones(): array
    {
        return array_values(array_filter((array) ($this->row['time_zones'] ?? []), 'is_string'));
    }

    /**
     * @return list<string>
     */
    public function languages(): array
    {
        return array_values(array_filter((array) ($this->row['languages'] ?? []), 'is_string'));
    }

    /**
     * @return list<string>
     */
    public function neighbours(): array
    {
        return array_values(array_filter((array) ($this->row['neighbours'] ?? []), 'is_string'));
    }

    /**
     * The country's own postal-code pattern (a PCRE body without delimiters), or null
     * when it has none or it is unknown.
     */
    public function postalCodeRegex(): ?string
    {
        return $this->string('postal_code_regex');
    }

    public function postalCodeFormat(): ?string
    {
        return $this->string('postal_code_format');
    }
}
