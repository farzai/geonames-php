<?php

namespace Farzai\Geonames\Resource;

use Farzai\Geonames\Responses\ResponseInterface;
use JsonSerializable;

interface ResourceInterface extends JsonSerializable
{
    /**
     * Get response
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getResponse(): ResponseInterface;

    /**
     * Return response as array
     *
     * @return array
     */
    public function asArray(): array;

    /**
     * Return response as json
     *
     * @return string
     */
    public function asJson(): string;

    /**
     * To string
     *
     * @return string
     */
    public function __toString(): string;
}
