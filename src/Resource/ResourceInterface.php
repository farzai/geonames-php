<?php

namespace Farzai\Geonames\Resource;

use Farzai\Geonames\Responses\ResponseInterface;
use JsonSerializable;

interface ResourceInterface extends JsonSerializable
{
    /**
     * Get response
     */
    public function getResponse(): ResponseInterface;

    /**
     * Return response as array
     */
    public function asArray(): array;

    /**
     * Return response as json
     */
    public function asJson(): string;

    /**
     * To string
     */
    public function __toString(): string;
}
