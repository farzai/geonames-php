<?php

namespace Farzai\Geonames\Transports;

use Psr\Http\Message\ResponseInterface;

interface TransportInterface
{
    /**
     * Send request to geonames
     */
    public function sendRequest(string $method, string $url, array $options = []): ResponseInterface;
}
