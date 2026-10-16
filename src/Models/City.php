<?php

declare(strict_types=1);

namespace Farzai\Geonames\Models;

final class City extends Model
{
    use Named;

    public function lat(): ?float
    {
        return $this->float('lat');
    }

    public function lon(): ?float
    {
        return $this->float('lon');
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

    public function admin2GeonameId(): ?int
    {
        return $this->int('admin2_geoname_id');
    }

    public function population(): ?int
    {
        return $this->int('population');
    }

    /** 15000, 5000, 1000 or 500: the largest threshold the city meets. */
    public function populationTier(): ?int
    {
        return $this->int('population_tier');
    }

    public function isCapital(): bool
    {
        return $this->bool('is_capital');
    }

    public function timeZone(): ?string
    {
        return $this->string('time_zone');
    }

    public function featureClass(): ?string
    {
        return $this->string('feature_class');
    }

    public function featureCode(): ?string
    {
        return $this->string('feature_code');
    }

    public function elevationM(): ?int
    {
        return $this->int('elevation_m');
    }

    public function demM(): ?int
    {
        return $this->int('dem_m');
    }
}
