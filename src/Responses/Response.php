<?php

namespace Farzai\Geonames\Responses;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class Response implements ResponseInterface
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    public function __construct(PsrResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getPsrResponse(): PsrResponseInterface
    {
        return $this->response;
    }

    /**
     * Is response successful
     */
    public function isSuccessful(): bool
    {
        return $this->response->getStatusCode() >= 200 && $this->response->getStatusCode() < 300;
    }

    /**
     * Get response body
     */
    public function getBody(): string
    {
        return $this->response->getBody()->getContents();
    }
}
