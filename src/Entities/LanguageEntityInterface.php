<?php

namespace Farzai\Geonames\Entities;

interface LanguageEntityInterface extends EntityInterface
{
    /**
     * Get language name
     *
     * @return string
     */
    public function getName();
}
