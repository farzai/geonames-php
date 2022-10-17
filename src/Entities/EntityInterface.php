<?php

namespace Farzai\Geonames\Entities;

use ArrayAccess;
use JsonSerializable;

interface EntityInterface extends ArrayAccess, JsonSerializable
{
    /**
     * Get entity id
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Get identify key
     *
     * @return string
     */
    public function getIdentifyKeyName(): string;

    /**
     * Get entity as array
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * For printing
     *
     * @return string
     */
    public function __toString(): string;
}
