<?php

namespace Farzai\Geonames\Transports;

use Psr\Http\Message\ResponseInterface;

interface TransportInterface
{
    /**
     * Send request to geonames
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest(string $method, string $url, array $options = []): ResponseInterface;
}
