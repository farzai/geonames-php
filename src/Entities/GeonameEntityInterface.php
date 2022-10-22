<?php

namespace Farzai\Geonames\Entities;

interface GeonameEntityInterface extends EntityInterface
{
    /**
     * Get geoname name
     *
     * @return string
     */
    public function getName();

    /**
     * Get country code
     * ISO-3166 2-letter country code, 2 characters
     *
     * @return string
     */
    public function getCountryCode();
}
