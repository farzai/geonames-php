<?php

declare(strict_types=1);

namespace Farzai\Geonames\Downloader;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * Downloads gazetteer (geographical feature) data from the GeoNames database.
 *
 * This downloader fetches gazetteer ZIP files from the GeoNames export server
 * for specific countries or all countries combined, along with administrative
 * code mapping files.
 */
class GazetteerDownloader extends AbstractDownloader
{
    /**
     * Base URL for GeoNames gazetteer downloads.
     */
    private const BASE_URL = 'https://download.geonames.org/export/dump/';

    /**
     * Admin code files required for resolving administrative division names.
     */
    private const ADMIN_CODE_FILES = [
        'admin1CodesASCII.txt',
        'admin2Codes.txt',
    ];

    /**
     * Download gazetteer data for a specific country.
     *
     * Downloads the country's gazetteer ZIP file along with administrative
     * code mapping files (admin1CodesASCII.txt and admin2Codes.txt).
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code (e.g., 'US', 'TH')
     * @param  string  $destination  Directory path where files will be saved
     *
     * @throws GeonamesException When the download fails
     */
    public function download(string $countryCode, string $destination): void
    {
        $filename = strtoupper($countryCode).'.zip';
        $url = $this->getBaseUrl().$filename;

        $this->downloadWithProgress($url, $destination.'/'.$filename);
        $this->downloadAdminCodes($destination);
    }

    /**
     * Download gazetteer data for all countries.
     *
     * Downloads the allCountries.zip file which contains gazetteer data
     * for all available countries, along with administrative code mapping files.
     *
     * @param  string  $destination  Directory path where files will be saved
     *
     * @throws GeonamesException When the download fails
     */
    public function downloadAll(string $destination): void
    {
        $url = $this->getBaseUrl().'allCountries.zip';
        $this->downloadWithProgress($url, $destination.'/allCountries.zip');
        $this->downloadAdminCodes($destination);
    }

    /**
     * Download administrative code mapping files.
     *
     * Downloads admin1CodesASCII.txt and admin2Codes.txt which are required
     * to resolve administrative division codes to human-readable names.
     *
     * @param  string  $destination  Directory path where files will be saved
     *
     * @throws GeonamesException When the download fails
     */
    private function downloadAdminCodes(string $destination): void
    {
        foreach (self::ADMIN_CODE_FILES as $file) {
            $url = $this->getBaseUrl().$file;

            $this->output?->writeln(sprintf('<info>Downloading %s...</info>', $file));
            $this->downloadWithProgress($url, $destination.'/'.$file);
        }
    }

    /**
     * Get the base URL for gazetteer downloads.
     *
     * @return string The base URL
     */
    protected function getBaseUrl(): string
    {
        return self::BASE_URL;
    }
}
