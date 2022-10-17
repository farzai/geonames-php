<?php

namespace Farzai\Geonames\Entities;

/**
 * Class LanguageEntity
 *
 * @property string $iso_639_3
 * @property string $iso_639_2
 * @property string $iso_639_1
 * @property string $name
 */
class LanguageEntity extends AbstractEntity implements LanguageEntityInterface
{
    /**
     * Get fields
     *
     * @return array
     */
    public static function getFields(): array
    {
        return [
            'iso_639_3',
            'iso_639_2',
            'iso_639_1',
            'name',
        ];
    }

    /**
     * Get identify key
     *
     * @return string
     */
    public function getIdentifyKeyName(): string
    {
        return 'iso_639_3';
    }

    /**
     * Get language code
     * (ISO 639-3)
     *
     * @return string
     */
    public function getCode()
    {
        return $this->iso_639_3;
    }

    /**
     * Get language name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
