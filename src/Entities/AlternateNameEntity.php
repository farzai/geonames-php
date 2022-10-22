<?php

namespace Farzai\Geonames\Entities;

/**
 * Class AlternateNameEntity
 *
 * @property string $id             - The id of this alternate name
 * @property string $geoname_id    - geonameId referring to id in table 'geoname'
 * @property string $iso_language - iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link for a website, varchar(7)
 * @property string $name         - alternate name or name variant, varchar(400)
 * @property bool $is_preferred_name - '1', if this alternate name is an official/preferred name
 * @property bool $is_short_name    - '1', if this is a short name like 'California' for 'State of California'
 * @property bool $is_colloquial   - '1', if this alternate name is a colloquial or slang term. Example: 'Big Apple' for 'New York'.
 * @property bool $is_historic   - '1', if this alternate name is historic and was used in the past. Example 'Bombay' for 'Mumbai'.
 * @property string $from from period when the name was used (in ISO 8601 format: YYYY-MM-DD)
 * @property string $to to period when the name was used (in ISO 8601 format: YYYY-MM-DD)
 */
class AlternateNameEntity extends AbstractEntity implements AlternateNameEntityInterface
{
    /**
     * Cast attributes
     *
     * @var array
     */
    protected $casts = [
        'id' => 'string',
        'geoname_id' => 'string',
        'is_preferred_name' => 'bool',
        'is_short_name' => 'bool',
        'is_colloquial' => 'bool',
        'is_historic' => 'bool',
    ];

    /**
     * Get identify key
     *
     * @return string
     */
    public function getIdentifyKeyName(): string
    {
        return 'id';
    }

    /**
     * Get geoname id
     *
     * @return mixed
     */
    public function getGeonameId()
    {
        return $this->geoname_id;
    }

    /**
     * Get alternate name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get language code
     *
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->iso_language;
    }

    /**
     * Get fields
     *
     * @return array
     */
    public static function getFields(): array
    {
        return [
            'id',
            'geoname_id',
            'iso_language',
            'name',
            'is_preferred_name',
            'is_short_name',
            'is_colloquial',
            'is_historic',
            'from',
            'to',
        ];
    }
}
