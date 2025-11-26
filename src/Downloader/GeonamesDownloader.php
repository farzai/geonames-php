<?php

declare(strict_types=1);

namespace Farzai\Geonames\Downloader;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * Downloads postal code data from the GeoNames database.
 *
 * This downloader fetches postal code ZIP files from the GeoNames
 * export server for specific countries or all countries combined.
 */
class GeonamesDownloader extends AbstractDownloader
{
    /**
     * Base URL for GeoNames postal code downloads.
     */
    private const BASE_URL = 'https://download.geonames.org/export/zip/';

    /**
     * Download postal code data for a specific country.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code (e.g., 'US', 'TH')
     * @param  string  $destination  Directory path where the ZIP file will be saved
     *
     * @throws GeonamesException When the download fails
     */
    public function download(string $countryCode, string $destination): void
    {
        $filename = strtoupper($countryCode).'.zip';
        $url = $this->getBaseUrl().$filename;

        $this->downloadWithProgress($url, $destination.'/'.$filename);
    }

    /**
     * Download postal codes for all countries.
     *
     * Downloads the allCountries.zip file which contains postal codes
     * for all available countries in a single archive.
     *
     * @param  string  $destination  Directory path where the ZIP file will be saved
     *
     * @throws GeonamesException When the download fails
     */
    public function downloadAll(string $destination): void
    {
        $url = $this->getBaseUrl().'allCountries.zip';
        $this->downloadWithProgress($url, $destination.'/allCountries.zip');
    }

    /**
     * Get the base URL for postal code downloads.
     *
     * @return string The base URL
     */
    protected function getBaseUrl(): string
    {
        return self::BASE_URL;
    }
}
