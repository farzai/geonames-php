<?php

namespace Farzai\Geonames\Entities;

interface AlternateNameEntityInterface extends EntityInterface
{
    /**
     * Get geoname id
     *
     * @return mixed
     */
    public function getGeonameId();

    /**
     * Get alternate name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get language code
     *
     * @return string
     */
    public function getLanguageCode(): string;
}
