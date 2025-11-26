<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * Converts GeoNames postal code data from ZIP files to JSON format.
 *
 * This converter extracts postal code data from GeoNames ZIP archives
 * and outputs it as a JSON file with streaming support for memory efficiency.
 */
class PostalCodeConverter extends AbstractConverter
{
    /**
     * Process the postal code data file and write to JSON output.
     *
     * @param  string  $txtFile  Path to the source TXT file containing postal code data
     * @param  string  $outputFile  Path to the output JSON file
     *
     * @throws GeonamesException When processing fails
     */
    protected function processFile(string $txtFile, string $outputFile): void
    {
        $totalLines = $this->countLines($txtFile);
        $progressBar = $this->createProgressBar($totalLines);

        $handle = fopen($outputFile, 'wb');
        if ($handle === false) {
            throw GeonamesException::fileOperationFailed('open for writing', $outputFile);
        }

        try {
            fwrite($handle, "[\n");
            $first = true;

            foreach ($this->streamPostalCodeRecords($txtFile) as $record) {
                if (! $first) {
                    fwrite($handle, ",\n");
                }
                fwrite($handle, json_encode($record, JSON_UNESCAPED_UNICODE));
                $first = false;

                $progressBar?->advance();
            }

            fwrite($handle, "\n]");
        } finally {
            fclose($handle);
            $this->finishProgressBar($progressBar);
        }
    }
}
