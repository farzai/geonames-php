<?php

namespace Farzai\Geonames\Entities;

interface CountryEntityInterface extends EntityInterface
{
    /**
     * Get country name
     *
     * @return string
     */
    public function getName();
}
