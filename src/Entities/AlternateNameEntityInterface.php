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
     */
    public function getName(): string;

    /**
     * Get language code
     */
    public function getLanguageCode(): string;
}
