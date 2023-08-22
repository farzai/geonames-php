<?php

namespace Farzai\Geonames\Responses;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface ResponseInterface
{
    public function getPsrResponse(): PsrResponseInterface;

    /**
     * Is response successful
     */
    public function isSuccessful(): bool;

    /**
     * Get response body
     */
    public function getBody(): string;
}
