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
     *
     * @return string
     */
    public function getCountryCode();
}
