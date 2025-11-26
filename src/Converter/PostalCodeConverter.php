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
            $this->writeToHandle($handle, "[\n", $outputFile);
            $first = true;

            foreach ($this->streamPostalCodeRecords($txtFile) as $record) {
                if (! $first) {
                    $this->writeToHandle($handle, ",\n", $outputFile);
                }

                $json = json_encode($record, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    throw GeonamesException::fileOperationFailed('encode JSON', $outputFile);
                }

                $this->writeToHandle($handle, $json, $outputFile);
                $first = false;

                $progressBar?->advance();
            }

            $this->writeToHandle($handle, "\n]", $outputFile);
        } finally {
            fclose($handle);
            $this->finishProgressBar($progressBar);
        }
    }

    /**
     * Write content to a file handle with error checking.
     *
     * @param  resource  $handle  The file handle to write to
     * @param  string  $content  The content to write
     * @param  string  $outputFile  The output file path (for error messages)
     *
     * @throws GeonamesException When the write operation fails
     */
    private function writeToHandle($handle, string $content, string $outputFile): void
    {
        if (fwrite($handle, $content) === false) {
            throw GeonamesException::fileOperationFailed('write', $outputFile);
        }
    }
}
