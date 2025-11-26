<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * Abstract base class for Gazetteer data converters.
 *
 * Extends AbstractConverter with additional functionality for handling
 * administrative code lookups (admin1 and admin2 codes to names).
 */
abstract class AbstractGazetteerConverter extends AbstractConverter
{
    /**
     * Minimum number of fields required for a valid gazetteer record.
     */
    protected const GAZETTEER_FIELD_COUNT = 19;

    /**
     * Mapping of admin1 codes to names.
     * Format: ['countryCode.admin1Code' => 'name']
     *
     * @var array<string, string>
     */
    protected array $admin1Codes = [];

    /**
     * Mapping of admin2 codes to names.
     * Format: ['countryCode.admin1Code.admin2Code' => 'name']
     *
     * @var array<string, string>
     */
    protected array $admin2Codes = [];

    /**
     * The directory containing admin code files.
     */
    protected string $adminCodesDir = '';

    /**
     * Convert a GeoNames gazetteer ZIP file to the target format.
     *
     * Overrides the parent method to accept the admin codes directory
     * and load admin codes before processing.
     *
     * @param  string  $zipFile  Path to the source ZIP file containing GeoNames data
     * @param  string  $outputFile  Path where the converted output should be written
     * @param  string  $adminCodesDir  Path to the directory containing admin code files
     *
     * @throws GeonamesException When conversion fails due to file or data errors
     */
    public function convertWithAdminCodes(string $zipFile, string $outputFile, string $adminCodesDir): void
    {
        $this->adminCodesDir = $adminCodesDir;
        $this->loadAdminCodes($adminCodesDir);
        $this->convert($zipFile, $outputFile);
    }

    /**
     * Load administrative code mappings from files.
     *
     * Reads admin1CodesASCII.txt and admin2Codes.txt files to build
     * lookup tables for converting admin codes to human-readable names.
     *
     * @param  string  $adminCodesDir  Path to the directory containing admin code files
     *
     * @throws GeonamesException When admin code files cannot be read
     */
    protected function loadAdminCodes(string $adminCodesDir): void
    {
        $this->output?->writeln('<info>Loading administrative codes...</info>');

        $this->loadAdmin1Codes($adminCodesDir.'/admin1CodesASCII.txt');
        $this->loadAdmin2Codes($adminCodesDir.'/admin2Codes.txt');
    }

    /**
     * Load admin1 codes from file.
     *
     * @param  string  $filePath  Path to the admin1 codes file
     */
    private function loadAdmin1Codes(string $filePath): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", trim($line));
                if (count($parts) >= 2) {
                    $this->admin1Codes[$parts[0]] = $parts[1];
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Load admin2 codes from file.
     *
     * @param  string  $filePath  Path to the admin2 codes file
     */
    private function loadAdmin2Codes(string $filePath): void
    {
        if (! file_exists($filePath)) {
            return;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", trim($line));
                if (count($parts) >= 2) {
                    $this->admin2Codes[$parts[0]] = $parts[1];
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Get the admin1 name for a given country and admin1 code.
     *
     * @param  string  $countryCode  The ISO 3166-1 alpha-2 country code
     * @param  string  $admin1Code  The admin1 code
     * @return string The admin1 name, or empty string if not found
     */
    protected function getAdmin1Name(string $countryCode, string $admin1Code): string
    {
        return $this->admin1Codes[$countryCode.'.'.$admin1Code] ?? '';
    }

    /**
     * Get the admin2 name for a given country, admin1, and admin2 code.
     *
     * @param  string  $countryCode  The ISO 3166-1 alpha-2 country code
     * @param  string  $admin1Code  The admin1 code
     * @param  string  $admin2Code  The admin2 code
     * @return string The admin2 name, or empty string if not found
     */
    protected function getAdmin2Name(string $countryCode, string $admin1Code, string $admin2Code): string
    {
        return $this->admin2Codes[$countryCode.'.'.$admin1Code.'.'.$admin2Code] ?? '';
    }

    /**
     * Parse a gazetteer data line into a structured array.
     *
     * @param  string  $line  The raw TSV line from the gazetteer file
     * @return array<string, mixed>|null The parsed record, or null if invalid
     */
    protected function parseGazetteerLine(string $line): ?array
    {
        $fields = array_map('trim', explode("\t", $line));

        if (count($fields) < self::GAZETTEER_FIELD_COUNT) {
            return null;
        }

        $countryCode = $fields[8];
        $admin1Code = $fields[10];
        $admin2Code = $fields[11];

        return [
            'geoname_id' => (int) $fields[0],
            'name' => $fields[1],
            'ascii_name' => $fields[2],
            'alternate_names' => array_filter(explode(',', $fields[3])),
            'latitude' => (float) $fields[4],
            'longitude' => (float) $fields[5],
            'feature_class' => $fields[6],
            'feature_code' => $fields[7],
            'country_code' => $countryCode,
            'cc2' => array_filter(explode(',', $fields[9] ?? '')),
            'admin1_code' => $admin1Code,
            'admin1_name' => $this->getAdmin1Name($countryCode, $admin1Code),
            'admin2_code' => $admin2Code,
            'admin2_name' => $this->getAdmin2Name($countryCode, $admin1Code, $admin2Code),
            'admin3_code' => $fields[12],
            'admin4_code' => $fields[13],
            'population' => (int) ($fields[14] ?? 0),
            'elevation' => ! empty($fields[15]) ? (int) $fields[15] : null,
            'dem' => ! empty($fields[16]) ? (int) $fields[16] : null,
            'timezone' => $fields[17],
            'modification_date' => $fields[18],
        ];
    }
}
