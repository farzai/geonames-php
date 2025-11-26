<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Farzai\Geonames\Exceptions\GeonamesException;
use Generator;

/**
 * Converts GeoNames gazetteer data from ZIP files to JSON format.
 *
 * This converter extracts geographical feature data from GeoNames ZIP archives
 * and outputs it as a JSON file with administrative code name resolution.
 * Uses streaming to handle large files with minimal memory usage.
 */
class GazetteerConverter extends AbstractGazetteerConverter
{
    /**
     * Process the gazetteer data file and write to JSON output.
     *
     * Uses streaming to process large files with O(1) memory complexity.
     *
     * @param  string  $txtFile  Path to the source TXT file containing gazetteer data
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
            $this->writeToHandle($handle, '[', $outputFile);
            $first = true;

            foreach ($this->streamGazetteerRecords($txtFile) as $record) {
                if (! $first) {
                    $this->writeToHandle($handle, ',', $outputFile);
                }

                $json = json_encode($record, JSON_UNESCAPED_UNICODE);
                if ($json === false) {
                    throw GeonamesException::fileOperationFailed('encode JSON', $outputFile);
                }

                $this->writeToHandle($handle, $json, $outputFile);
                $first = false;

                $progressBar?->advance();
            }

            $this->writeToHandle($handle, ']', $outputFile);
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

    /**
     * Stream gazetteer records from a TXT file.
     *
     * Uses a generator to yield records one at a time, enabling memory-efficient
     * processing of large files.
     *
     * @param  string  $txtFile  Path to the TXT file containing gazetteer data
     * @return Generator<int, array<string, mixed>> Yields gazetteer records
     *
     * @throws GeonamesException When the file cannot be opened
     */
    protected function streamGazetteerRecords(string $txtFile): Generator
    {
        $handle = fopen($txtFile, 'r');

        if ($handle === false) {
            throw GeonamesException::fileOperationFailed('open', $txtFile);
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmedLine = trim($line);
                if (empty($trimmedLine)) {
                    continue;
                }

                $record = $this->parseGazetteerLine($trimmedLine);
                if ($record !== null) {
                    yield $record;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
