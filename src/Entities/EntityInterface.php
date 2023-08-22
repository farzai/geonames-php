<?php

namespace Farzai\Geonames\Entities;

use ArrayAccess;
use JsonSerializable;

interface EntityInterface extends ArrayAccess, JsonSerializable
{
    /**
     * Get entity id
     */
    public function getIdentifier(): string;

    /**
     * Get identify key
     */
    public function getIdentifyKeyName(): string;

    /**
     * Get entity as array
     */
    public function toArray(): array;

    /**
     * For printing
     */
    public function __toString(): string;
}
