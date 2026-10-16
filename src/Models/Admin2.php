<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

final class Admin2 extends Model
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

    public function admin2Code(): ?string
    {
        return $this->string('admin2_code');
    }

    public function admin1GeonameId(): ?int
    {
        return $this->int('admin1_geoname_id');
    }

    public function iso31662(): ?string
    {
        return $this->string('iso_3166_2');
    }
}
