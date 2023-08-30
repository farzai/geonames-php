<?php

namespace Farzai\Geonames\Entities;

/**
 * Class GeonameEntity
 *
 * @property string $id
 * @property string $name
 * @property string $ascii_name
 * @property string $alternate_names
 * @property string $latitude
 * @property string $longitude
 * @property string $feature_class
 * @property string $feature_code
 * @property string $country_code ISO-3166 2-letter country code, 2 characters
 * @property string $cc2
 * @property string $admin1_code
 * @property string $admin2_code
 * @property string $admin3_code
 * @property string $admin4_code
 * @property string $population
 * @property string $elevation
 * @property string $dem
 * @property string $timezone
 * @property string $modification_date
 */
class GeonameEntity extends AbstractEntity implements GeonameEntityInterface
{
    protected $casts = [
        'id' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'population' => 'int',
        'elevation' => 'int',
    ];

    public static function getFields(): array
    {
        return [
            'id',
            'name',
            'ascii_name',
            'alternate_names',
            'latitude',
            'longitude',
            'feature_class',
            'feature_code',
            'country_code',
            'cc2',
            'admin1_code',
            'admin2_code',
            'admin3_code',
            'admin4_code',
            'population',
            'elevation',
            'dem',
            'timezone',
            'modification_date',
        ];
    }

    /**
     * Get identify key
     */
    public function getIdentifyKeyName(): string
    {
        return 'id';
    }

    /**
     * Get geoname name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get country code
     * ISO-3166 2-letter country code, 2 characters
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }
}
