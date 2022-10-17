<?php

namespace Farzai\Geonames\Entities;

interface CountryEntityInterface extends EntityInterface
{
    /**
     * Get country code
     *
     * @return string
     */
    public function getCode();

    /**
     * Get country name
     *
     * @return string
     */
    public function getName();
}
