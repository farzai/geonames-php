<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

/**
 * A postal row has no `geoname_id`. `precision()` says whether the code is a full
 * code or only a prefix (`partial`: CL, CN, IE, MT, AR, BR); never treat a partial
 * row as a complete code.
 */
final class PostalCode extends Model
{
    public function countryCode(): ?string
    {
        return $this->string('country_code');
    }

    public function postalCode(): ?string
    {
        return $this->string('postal_code');
    }

    public function placeName(): ?string
    {
        return $this->string('place_name');
    }

    public function admin1Name(): ?string
    {
        return $this->string('admin1_name');
    }

    public function admin1Code(): ?string
    {
        return $this->string('admin1_code');
    }

    public function admin2Name(): ?string
    {
        return $this->string('admin2_name');
    }

    public function admin2Code(): ?string
    {
        return $this->string('admin2_code');
    }

    public function lat(): ?float
    {
        return $this->float('lat');
    }

    public function lon(): ?float
    {
        return $this->float('lon');
    }

    /** `full` or `partial`; anything else a later minor adds reads as `partial`. */
    public function precision(): string
    {
        return ($this->row['precision'] ?? null) === 'full' ? 'full' : 'partial';
    }

    public function isFull(): bool
    {
        return $this->precision() === 'full';
    }

    /** `geonames` or `geonames_full`. */
    public function source(): ?string
    {
        return $this->string('source');
    }
}
