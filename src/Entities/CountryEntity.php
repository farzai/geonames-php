<?php

namespace Farzai\Geonames\Entities;

/**
 * Class CountryEntity
 *
 * @property string $iso
 * @property string $iso3
 * @property string $iso_numeric
 * @property string $fips
 * @property string $name
 * @property string $capital
 * @property string $area
 * @property string $population
 * @property string $continent
 * @property string $tld
 * @property string $currency_code
 * @property string $currency_name
 * @property string $phone
 * @property string $postal_code_format
 * @property string $postal_code_regex
 * @property string $languages
 * @property string $geoname_id
 * @property string $neighbours
 * @property string $equivalent_fips_code
 */
class CountryEntity extends AbstractEntity implements CountryEntityInterface
{
    /**
     * Cast attributes
     *
     * @var array
     */
    protected $casts = [
        'iso_numeric' => 'string',
        'area' => 'float',
        'geoname_id' => 'string',
        'population' => 'int',
        'phone' => 'string',
    ];

    /**
     * Get identify key
     *
     * @return string
     */
    public function getIdentifyKeyName(): string
    {
        return 'iso';
    }

    /**
     * Get country name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get fields
     *
     * @return array
     */
    public static function getFields(): array
    {
        return [
            'iso',
            'iso3',
            'iso_numeric',
            'fips',
            'name',
            'capital',
            'area',
            'population',
            'continent',
            'tld',
            'currency_code',
            'currency_name',
            'phone',
            'postal_code_format',
            'postal_code_regex',
            'languages',
            'geoname_id',
            'neighbours',
            'equivalent_fips_code',
        ];
    }
}
