<?php

declare(strict_types=1);

namespace Farzai\Geonames\Downloader;

use Farzai\Geonames\Exceptions\GeonamesException;
use Farzai\Transport\Transport;
use Farzai\Transport\TransportBuilder;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base class for GeoNames data downloaders.
 *
 * Provides shared functionality for downloading files from the GeoNames
 * servers with progress tracking and error handling.
 */
abstract class AbstractDownloader
{
    /**
     * Size of chunks when downloading files.
     */
    protected const CHUNK_SIZE = 8192;

    /**
     * The HTTP transport instance.
     */
    protected Transport $transport;

    /**
     * The console output interface for displaying progress.
     */
    protected ?OutputInterface $output = null;

    /**
     * Create a new downloader instance.
     *
     * @param  Transport|null  $transport  Optional pre-configured transport client
     */
    public function __construct(?Transport $transport = null)
    {
        $this->transport = $transport ?? TransportBuilder::make()->build();
    }

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
     * Download a file from a URL with progress tracking.
     *
     * The body is written to "<destination>.part" and renamed to the
     * destination only once it is known to be whole: a 200 response, as many
     * bytes as Content-Length announced, and, for a .zip, an archive that
     * opens. On any failure the partial file is removed and an earlier file
     * at the destination is left as it was, so a missing country, an outage
     * or a dropped connection never leaves a file that looks like the data.
     *
     * @param  string  $url  The URL to download from
     * @param  string  $destination  The local file path to save to
     *
     * @throws GeonamesException When the download fails
     */
    protected function downloadWithProgress(string $url, string $destination): void
    {
        try {
            $response = $this->transport->get($url)->send();
        } catch (\Throwable $e) {
            throw new GeonamesException(
                sprintf('Failed to download from %s: %s', $url, $e->getMessage()),
                0,
                $e
            );
        }

        $status = $response->getStatusCode();
        if ($status !== 200) {
            throw new GeonamesException(sprintf('Failed to download from %s: HTTP %d', $url, $status));
        }

        $contentLengthHeader = $response->getHeader('Content-Length');
        $totalSize = ! empty($contentLengthHeader) ? (int) $contentLengthHeader[0] : 0;
        $body = $response->getBody();

        $partial = $destination.'.part';
        $handle = fopen($partial, 'wb');
        if ($handle === false) {
            throw GeonamesException::fileOperationFailed('open for writing', $partial);
        }

        $progressBar = $this->createProgressBar($totalSize);

        try {
            $downloaded = 0;

            try {
                while (! $body->eof()) {
                    $chunk = $body->read(self::CHUNK_SIZE);
                    $bytesWritten = fwrite($handle, $chunk);

                    if ($bytesWritten === false || $bytesWritten !== strlen($chunk)) {
                        throw GeonamesException::fileOperationFailed('write', $partial);
                    }

                    $downloaded += $bytesWritten;
                    $progressBar?->setProgress($downloaded);
                }
            } finally {
                fclose($handle);
                $this->finishProgressBar($progressBar);
            }

            if ($totalSize > 0 && $downloaded !== $totalSize) {
                throw new GeonamesException(sprintf(
                    'Incomplete download from %s: received %d of %d bytes',
                    $url,
                    $downloaded,
                    $totalSize
                ));
            }

            if (str_ends_with(strtolower($destination), '.zip')) {
                $this->assertReadableZip($partial, $url);
            }

            if (! rename($partial, $destination)) {
                throw GeonamesException::fileOperationFailed('rename', $partial);
            }
        } catch (\Throwable $e) {
            if (file_exists($partial)) {
                unlink($partial);
            }

            throw $e;
        }
    }

    /**
     * The ZIP file name for a country, after checking the code is two letters.
     * The code becomes part of the URL and of the saved path, so anything
     * else (a path separator, "..") is refused before a request is made.
     *
     * @throws GeonamesException When the code is not two letters
     */
    protected function countryZipName(string $countryCode): string
    {
        if (preg_match('/^[A-Za-z]{2}$/', $countryCode) !== 1) {
            throw new GeonamesException(sprintf(
                'Invalid country code "%s": expected an ISO 3166-1 alpha-2 code such as TH',
                $countryCode
            ));
        }

        return strtoupper($countryCode).'.zip';
    }

    /**
     * Check that a downloaded file is a ZIP archive that opens and passes a
     * consistency check, so an error page or a cut-off body served without a
     * Content-Length is caught before it is saved as the data.
     *
     * @throws GeonamesException When the file is not a readable ZIP archive
     */
    protected function assertReadableZip(string $path, string $url): void
    {
        $zip = new \ZipArchive;
        $result = $zip->open($path, \ZipArchive::CHECKCONS);
        if ($result !== true) {
            throw new GeonamesException(sprintf(
                'Downloaded file from %s is not a readable ZIP archive (ZipArchive error %d)',
                $url,
                $result
            ));
        }
        $zip->close();
    }

    /**
     * Create and configure a progress bar for download tracking.
     *
     * @param  int  $totalSize  Total size in bytes to download
     * @return ProgressBar|null The configured progress bar, or null if no output is set
     */
    protected function createProgressBar(int $totalSize): ?ProgressBar
    {
        if ($this->output === null || $totalSize === 0) {
            return null;
        }

        $progressBar = new ProgressBar($this->output, $totalSize);
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
     * Get the base URL for downloading files.
     *
     * @return string The base URL
     */
    abstract protected function getBaseUrl(): string;
}
