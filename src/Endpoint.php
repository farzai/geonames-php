<?php

namespace Farzai\Geonames;

use Farzai\Transport\Contracts\ResponseInterface;
use Farzai\Transport\Request;
use Farzai\Transport\Response;
use Farzai\Transport\Transport;
use Psr\Http\Message\RequestInterface;

class Endpoint implements EndpointInterface
{
    const ENDPOINT = 'http://download.geonames.org/export/dump';

    /**
     * Http transport
     */
    private Transport $transport;

    /**
     * Endpoint constructor.
     */
    public function __construct(Transport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Get language codes
     */
    public function getLanguageCodes(): ResponseInterface
    {
        return $this->get('iso-languagecodes.txt');
    }

    /**
     * Get all countries
     */
    public function getCountryInfo(): ResponseInterface
    {
        return $this->get('countryInfo.txt');
    }

    /**
     * Get download page
     */
    public function getGeonamesDownloadPage(): ResponseInterface
    {
        return $this->get('/');
    }

    /**
     * Get geonames by country code
     */
    public function getGeonamesByCountryCode(string $countryCode): ResponseInterface
    {
        $countryCode = strtoupper(trim($countryCode));

        return $this->get("{$countryCode}.zip");
    }

    /**
     * Get alternate names page
     */
    public function getAlternateNamesDownloadPage(): ResponseInterface
    {
        return $this->get('/alternatenames');
    }

    /**
     * Get alternate names by country code
     */
    public function getAlternateNamesByCountryCode(string $countryCode): ResponseInterface
    {
        $countryCode = strtoupper(trim($countryCode));

        return $this->get("/alternatenames/{$countryCode}.zip");
    }

    /**
     * Call GET request
     */
    private function get(string $path): ResponseInterface
    {
        if (preg_match('/^https?:\/\//', $path)) {
            $endpoint = $path;
        } else {
            $endpoint = static::ENDPOINT.'/'.ltrim($path, '/');
        }

        return $this->send(new Request('GET', $endpoint));
    }

    protected function send(RequestInterface $request): ResponseInterface
    {
        return new Response($request, $this->transport->sendRequest(
            $request,
        ));
    }
}
