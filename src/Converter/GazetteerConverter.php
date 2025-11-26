<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * Converts GeoNames gazetteer data from ZIP files to JSON format.
 *
 * This converter extracts geographical feature data from GeoNames ZIP archives
 * and outputs it as a JSON file with administrative code name resolution.
 */
class GazetteerConverter extends AbstractGazetteerConverter
{
    /**
     * Process the gazetteer data file and write to JSON output.
     *
     * @param  string  $txtFile  Path to the source TXT file containing gazetteer data
     * @param  string  $outputFile  Path to the output JSON file
     *
     * @throws GeonamesException When processing fails
     */
    protected function processFile(string $txtFile, string $outputFile): void
    {
        $data = [];
        $lines = file($txtFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw GeonamesException::fileOperationFailed('read', $txtFile);
        }

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $record = $this->parseGazetteerLine($line);
            if ($record !== null) {
                $data[] = $record;
            }
        }

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonContent === false) {
            throw GeonamesException::fileOperationFailed('encode JSON', $outputFile);
        }

        $result = file_put_contents($outputFile, $jsonContent);
        if ($result === false) {
            throw GeonamesException::fileOperationFailed('write', $outputFile);
        }
    }
}
