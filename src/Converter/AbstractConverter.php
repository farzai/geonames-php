<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use DirectoryIterator;
use Farzai\Geonames\Contracts\ConverterInterface;
use Farzai\Geonames\Exceptions\GeonamesException;
use Generator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

/**
 * Abstract base class for GeoNames data converters.
 *
 * This class implements the Template Method design pattern, defining the skeleton
 * of the conversion algorithm while allowing subclasses to implement specific
 * output format handling.
 */
abstract class AbstractConverter implements ConverterInterface
{
    /**
     * Size of chunks when reading files for line counting.
     */
    protected const CHUNK_SIZE = 8192;

    /**
     * Batch size for bulk operations (e.g., MongoDB inserts).
     */
    protected const BATCH_SIZE = 1000;

    /**
     * Minimum number of fields required for a valid postal code record.
     */
    protected const POSTAL_CODE_FIELD_COUNT = 9;

    /**
     * The console output interface for displaying progress.
     */
    protected ?OutputInterface $output = null;

    /**
     * Set the console output interface for progress display.
     *
     * @param  OutputInterface  $output  The Symfony console output interface
     * @return static Returns self for method chaining
     */
    public function setOutput(OutputInterface $output): static
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Convert a GeoNames ZIP file to the target format.
     *
     * This is the template method that orchestrates the conversion process.
     * Subclasses must implement the writeOutput() method to handle the
     * specific output format.
     *
     * @param  string  $zipFile  Path to the source ZIP file containing GeoNames data
     * @param  string  $outputFile  Path where the converted output should be written
     *
     * @throws GeonamesException When conversion fails due to file or data errors
     */
    public function convert(string $zipFile, string $outputFile): void
    {
        $tempDir = $this->extractZipFile($zipFile);

        try {
            $txtFile = $this->findDataFile($tempDir);
            $this->processFile($txtFile, $outputFile);
        } finally {
            $this->cleanupTempDirectory($tempDir);
        }
    }

    /**
     * Extract the ZIP file to a temporary directory.
     *
     * @param  string  $zipFile  Path to the ZIP file
     * @return string Path to the temporary directory containing extracted files
     *
     * @throws GeonamesException When the ZIP file cannot be opened or extracted
     */
    protected function extractZipFile(string $zipFile): string
    {
        $zip = new ZipArchive;

        if ($zip->open($zipFile) !== true) {
            throw GeonamesException::zipOperationFailed($zipFile);
        }

        $tempDir = sys_get_temp_dir().'/geonames_'.uniqid();

        if (! mkdir($tempDir) && ! is_dir($tempDir)) {
            $zip->close();
            throw GeonamesException::fileOperationFailed('create directory', $tempDir);
        }

        $zip->extractTo($tempDir);
        $zip->close();

        return $tempDir;
    }

    /**
     * Find the data file (TXT) within the extracted directory.
     *
     * @param  string  $directory  Path to search for the data file
     * @return string Path to the found TXT file
     *
     * @throws GeonamesException When no TXT file is found
     */
    protected function findDataFile(string $directory): string
    {
        $files = glob($directory.'/*.txt');

        if (empty($files)) {
            throw GeonamesException::dataNotFound('.txt file', 'ZIP archive');
        }

        return $files[0];
    }

    /**
     * Clean up the temporary directory and its contents.
     *
     * @param  string  $tempDir  Path to the temporary directory to clean up
     */
    protected function cleanupTempDirectory(string $tempDir): void
    {
        if (! is_dir($tempDir)) {
            return;
        }

        $files = new DirectoryIterator($tempDir);
        foreach ($files as $file) {
            if (! $file->isDot()) {
                unlink($file->getPathname());
            }
        }
        rmdir($tempDir);
    }

    /**
     * Count the number of lines in a file efficiently.
     *
     * Uses chunked reading to handle large files without excessive memory usage.
     *
     * @param  string  $file  Path to the file to count lines in
     * @return int The number of lines in the file
     *
     * @throws GeonamesException When the file cannot be opened
     */
    protected function countLines(string $file): int
    {
        $handle = fopen($file, 'r');

        if ($handle === false) {
            throw GeonamesException::fileOperationFailed('open', $file);
        }

        try {
            $lines = 0;
            while (! feof($handle)) {
                $chunk = fread($handle, self::CHUNK_SIZE);
                if ($chunk !== false) {
                    $lines += substr_count($chunk, "\n");
                }
            }

            return $lines;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Stream postal code records from a TXT file.
     *
     * Uses a generator to yield records one at a time, enabling memory-efficient
     * processing of large files.
     *
     * @param  string  $txtFile  Path to the TXT file containing postal code data
     * @return Generator<int, array<string, mixed>> Yields postal code records
     *
     * @throws GeonamesException When the file cannot be opened
     */
    protected function streamPostalCodeRecords(string $txtFile): Generator
    {
        $handle = fopen($txtFile, 'r');

        if ($handle === false) {
            throw GeonamesException::fileOperationFailed('open', $txtFile);
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $data = str_getcsv(trim($line), "\t", '"', '\\');

                if (count($data) < self::POSTAL_CODE_FIELD_COUNT) {
                    continue;
                }

                yield [
                    'country_code' => $data[0],
                    'postal_code' => $data[1],
                    'place_name' => $data[2],
                    'admin_name1' => $data[3],
                    'admin_code1' => $data[4],
                    'admin_name2' => $data[5] ?? '',
                    'admin_code2' => $data[6] ?? '',
                    'admin_name3' => $data[7] ?? '',
                    'admin_code3' => $data[8] ?? '',
                    'latitude' => isset($data[9]) ? (float) $data[9] : null,
                    'longitude' => isset($data[10]) ? (float) $data[10] : null,
                    'accuracy' => isset($data[11]) ? (int) $data[11] : null,
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Create and configure a progress bar for the conversion process.
     *
     * @param  int  $totalLines  Total number of lines to process
     * @return ProgressBar|null The configured progress bar, or null if no output is set
     */
    protected function createProgressBar(int $totalLines): ?ProgressBar
    {
        if ($this->output === null || $totalLines <= 0) {
            return null;
        }

        $progressBar = new ProgressBar($this->output, $totalLines);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        return $progressBar;
    }

    /**
     * Finish the progress bar and add a newline.
     *
     * @param  ProgressBar|null  $progressBar  The progress bar to finish
     */
    protected function finishProgressBar(?ProgressBar $progressBar): void
    {
        if ($progressBar !== null) {
            $progressBar->finish();
            $this->output?->writeln('');
        }
    }

    /**
     * Process the data file and write to the output.
     *
     * Subclasses must implement this method to handle the specific output format.
     *
     * @param  string  $txtFile  Path to the source TXT file
     * @param  string  $outputFile  Path to the output file or resource identifier
     *
     * @throws GeonamesException When processing fails
     */
    abstract protected function processFile(string $txtFile, string $outputFile): void;
}
