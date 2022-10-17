<?php

namespace Farzai\Geonames\Responses;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface
{
    /**
     * @return PsrResponseInterface
     */
    public function getPsrResponse(): PsrResponseInterface;

    /**
     * Is response successful
     *
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Get response body
     *
     * @return string
     */
    public function getBody(): string;
}
