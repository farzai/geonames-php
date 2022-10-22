<?php

namespace Farzai\Geonames;

use Farzai\Geonames\Responses\ResponseInterface;

interface EndpointInterface
{
    /**
     * Get language codes
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getLanguageCodes(): ResponseInterface;

    /**
     * Get all countries
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getCountryInfo(): ResponseInterface;

    /**
     * Get download page
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getGeonamesDownloadPage(): ResponseInterface;

    /**
     * Get geonames by country code
     *
     * @param  string  $file
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getGeonamesByCountryCode(string $countryCode): ResponseInterface;

    /**
     * Get alternate names page
     *
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getAlternateNamesDownloadPage(): ResponseInterface;

    /**
     * Get alternate names by country code
     *
     * @param  string  $countryCode
     * @return \Farzai\Geonames\Responses\ResponseInterface
     */
    public function getAlternateNamesByCountryCode(string $countryCode): ResponseInterface;
}
