<?php

namespace Farzai\Geonames;

use Farzai\Transport\Contracts\ResponseInterface;

interface EndpointInterface
{
    /**
     * Get language codes
     */
    public function getLanguageCodes(): ResponseInterface;

    /**
     * Get all countries
     */
    public function getCountryInfo(): ResponseInterface;

    /**
     * Get download page
     */
    public function getGeonamesDownloadPage(): ResponseInterface;

    /**
     * Get geonames by country code
     *
     * @param  string  $file
     */
    public function getGeonamesByCountryCode(string $countryCode): ResponseInterface;

    /**
     * Get alternate names page
     */
    public function getAlternateNamesDownloadPage(): ResponseInterface;

    /**
     * Get alternate names by country code
     */
    public function getAlternateNamesByCountryCode(string $countryCode): ResponseInterface;
}
