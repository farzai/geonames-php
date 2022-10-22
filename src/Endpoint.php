<?php

namespace Farzai\Geonames;

use Farzai\Geonames\Responses\Response;
use Farzai\Geonames\Responses\ResponseInterface;
use Farzai\Geonames\Transports\TransportInterface;

class Endpoint implements EndpointInterface
{
    const ENDPOINT = 'http://download.geonames.org/export/dump';

    /**
     * Http transport
     *
     * @var TransportInterface
     */
    private TransportInterface $transport;

    /**
     * Endpoint constructor.
     *
     * @param  TransportInterface  $transport
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Get language codes
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getLanguageCodes(): Response
    {
        return $this->get('iso-languagecodes.txt');
    }

    /**
     * Get all countries
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getCountryInfo(): ResponseInterface
    {
        return $this->get('countryInfo.txt');
    }

    /**
     * Get download page
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getGeonamesDownloadPage(): ResponseInterface
    {
        return $this->get('/');
    }

    /**
     * Get geonames by country code
     *
     * @param  string  $countryCode
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getGeonamesByCountryCode(string $countryCode): ResponseInterface
    {
        $countryCode = strtoupper(trim($countryCode));

        return $this->get("{$countryCode}.zip");
    }

    /**
     * Get alternate names page
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getAlternateNamesDownloadPage(): ResponseInterface
    {
        return $this->get('/alternatenames');
    }

    /**
     * Get alternate names by country code
     *
     * @param  string  $countryCode
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getAlternateNamesByCountryCode(string $countryCode): ResponseInterface
    {
        $countryCode = strtoupper(trim($countryCode));

        return $this->get("/alternatenames/{$countryCode}.zip");
    }

    /**
     * Set transport
     * Transport is used to send request to geonames
     *
     * @param  TransportInterface  $transport
     */
    public function setTransport(TransportInterface $transport): void
    {
        $this->transport = $transport;
    }

    /**
     * Call GET request
     *
     * @param  string  $endpoint
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    private function get(string $path)
    {
        if (preg_match('/^https?:\/\//', $path)) {
            $endpoint = $path;
        } else {
            $endpoint = static::ENDPOINT.'/'.ltrim($path, '/');
        }

        return new Response($this->transport->sendRequest('GET', $endpoint));
    }
}
