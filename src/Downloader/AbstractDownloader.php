<?php

declare(strict_types=1);

namespace Farzai\Geonames\Downloader;

use Farzai\Geonames\Exceptions\GeonamesException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
     * The Guzzle HTTP client instance.
     */
    protected Client $client;

    /**
     * The console output interface for displaying progress.
     */
    protected ?OutputInterface $output = null;

    /**
     * Whether to verify SSL certificates.
     */
    protected bool $verifySsl;

    /**
     * Create a new downloader instance.
     *
     * @param  Client|null  $client  Optional pre-configured Guzzle client
     * @param  bool  $verifySsl  Whether to verify SSL certificates (default: true)
     */
    public function __construct(?Client $client = null, bool $verifySsl = true)
    {
        $this->verifySsl = $verifySsl;
        $this->client = $client ?? new Client([
            'verify' => $this->verifySsl,
        ]);
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
     * @param  string  $url  The URL to download from
     * @param  string  $destination  The local file path to save to
     *
     * @throws GeonamesException When the download fails
     */
    protected function downloadWithProgress(string $url, string $destination): void
    {
        try {
            $response = $this->client->get($url, [
                'stream' => true,
            ]);
        } catch (GuzzleException $e) {
            throw new GeonamesException(
                sprintf('Failed to download from %s: %s', $url, $e->getMessage()),
                0,
                $e
            );
        }

        $contentLengthHeader = $response->getHeader('Content-Length');
        $totalSize = ! empty($contentLengthHeader) ? (int) $contentLengthHeader[0] : 0;
        $body = $response->getBody();

        $progressBar = $this->createProgressBar($totalSize);

        $handle = fopen($destination, 'wb');
        if ($handle === false) {
            throw GeonamesException::fileOperationFailed('open for writing', $destination);
        }

        try {
            $downloaded = 0;

            while (! $body->eof()) {
                $chunk = $body->read(self::CHUNK_SIZE);
                $bytesWritten = fwrite($handle, $chunk);

                if ($bytesWritten === false) {
                    throw GeonamesException::fileOperationFailed('write', $destination);
                }

                $downloaded += strlen($chunk);
                $progressBar?->setProgress($downloaded);
            }
        } finally {
            fclose($handle);
            $this->finishProgressBar($progressBar);
        }
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
