<?php

declare(strict_types=1);

namespace Farzai\Geonames\Contracts;

use Farzai\Geonames\Exceptions\GeonamesException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contract for GeoNames data converters.
 *
 * Implementations of this interface convert GeoNames data files
 * from ZIP archives into various output formats (JSON, MongoDB, etc.).
 */
interface ConverterInterface
{
    /**
     * Set the console output interface for progress display.
     *
     * @param  OutputInterface  $output  The Symfony console output interface
     * @return static Returns self for method chaining
     */
    public function setOutput(OutputInterface $output): static;

    /**
     * Convert a GeoNames ZIP file to the target format.
     *
     * @param  string  $zipFile  Path to the source ZIP file containing GeoNames data
     * @param  string  $outputFile  Path where the converted output should be written
     *
     * @throws GeonamesException When conversion fails due to file or data errors
     */
    public function convert(string $zipFile, string $outputFile): void;
}
