<?php

namespace Farzai\Geonames\Transports;

use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Http\Message\ResponseInterface;

class GuzzleHttpTransport implements TransportInterface
{
    /**
     * Guzzle http client
     *
     * @var GuzzleHttpClient
     */
    private GuzzleHttpClient $client;

    /**
     * GuzzleHttpTransport constructor.
     *
     * @param  array  $config
     */
    public function __construct(array $config = [])
    {
        // Check guzzle http client is installed
        if (! class_exists('GuzzleHttp\Client')) {
            throw new \RuntimeException('Guzzle http client is not installed');
        }

        $this->client = new GuzzleHttpClient(array_merge([
            // Default timeout is 30 seconds
            'timeout' => 30,

            // Default connect timeout is 5 seconds
            'connect_timeout' => 5,
        ], $config));
    }

    /**
     * Send request to geonames
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }
}
