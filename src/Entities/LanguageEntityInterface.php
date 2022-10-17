<?php

namespace Farzai\Geonames\Entities;

interface LanguageEntityInterface extends EntityInterface
{
    /**
     * Get language code
     * (ISO 639-3)
     *
     * @return string
     */
    public function getCode();

    /**
     * Get language name
     *
     * @return string
     */
    public function getName();
}
