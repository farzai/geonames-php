<?php

namespace Farzai\Geonames\Responses;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class Response implements ResponseInterface
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     * @param  \Psr\Http\Message\ResponseInterface  $response
     */
    public function __construct(PsrResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return PsrResponseInterface
     */
    public function getPsrResponse(): PsrResponseInterface
    {
        return $this->psrResponse;
    }

    /**
     * Is response successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->response->getStatusCode() >= 200 && $this->response->getStatusCode() < 300;
    }

    /**
     * Get response body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->response->getBody()->getContents();
    }
}
