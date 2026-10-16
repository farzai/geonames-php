<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

/**
 * A first-level division. The key is `geoname_id` and `geonamesCode()` (`TH.40`);
 * `iso31662()` is a nullable attribute only and is never a join key.
 */
final class Admin1 extends Model
{
    use Named;

    public function geonamesCode(): ?string
    {
        return $this->string('geonames_code');
    }

    public function countryCode(): ?string
    {
        return $this->string('country_code');
    }

    public function admin1Code(): ?string
    {
        return $this->string('admin1_code');
    }

    public function iso31662(): ?string
    {
        return $this->string('iso_3166_2');
    }
}
