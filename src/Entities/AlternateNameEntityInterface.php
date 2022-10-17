<?php

namespace Farzai\Geonames\Entities;

interface AlternateNameEntityInterface extends EntityInterface
{
    /**
     * Get alternate id
     *
     * @return mixed
     */
    public function getId();

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
    public function getName();

    /**
     * Get country code
     *
     * @return string
     */
    public function getCountryCode();
}
