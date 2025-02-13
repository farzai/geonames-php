<?php

declare(strict_types=1);

namespace Farzai\Geonames\Downloader;

use GuzzleHttp\Client;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class GazetteerDownloader
{
    private const BASE_URL = 'https://download.geonames.org/export/dump/';

    private Client $client;

    private ?OutputInterface $output = null;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client([
            'verify' => false,
        ]);
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Download country data
     */
    public function download(string $countryCode, string $destination): void
    {
        $filename = strtoupper($countryCode).'.zip';
        $url = self::BASE_URL.$filename;

        $this->downloadWithProgress($url, $destination.'/'.$filename);

        // Download admin codes
        $this->downloadAdminCodes($destination);
    }

    /**
     * Download all countries data
     */
    public function downloadAll(string $destination): void
    {
        $url = self::BASE_URL.'allCountries.zip';
        $this->downloadWithProgress($url, $destination.'/allCountries.zip');

        // Download admin codes
        $this->downloadAdminCodes($destination);
    }

    /**
     * Download admin codes files
     */
    private function downloadAdminCodes(string $destination): void
    {
        $files = [
            'admin1CodesASCII.txt',
            'admin2Codes.txt',
        ];

        foreach ($files as $file) {
            $url = self::BASE_URL.$file;
            if ($this->output) {
                $this->output->writeln(sprintf('<info>Downloading %s...</info>', $file));
            }
            $this->downloadWithProgress($url, $destination.'/'.$file);
        }
    }

    /**
     * Download file with progress bar
     */
    private function downloadWithProgress(string $url, string $destination): void
    {
        $response = $this->client->get($url, [
            'stream' => true,
        ]);

        $totalSize = (int) $response->getHeader('Content-Length')[0];
        $body = $response->getBody();

        $progressBar = null;
        if ($this->output) {
            $progressBar = new ProgressBar($this->output, $totalSize);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
            $progressBar->start();
        }

        $handle = fopen($destination, 'wb');
        $downloaded = 0;

        while (! $body->eof()) {
            $chunk = $body->read(8192);
            fwrite($handle, $chunk);
            $downloaded += strlen($chunk);

            if ($progressBar) {
                $progressBar->setProgress($downloaded);
            }
        }

        if ($progressBar) {
            $progressBar->finish();
            $this->output->writeln('');
        }

        fclose($handle);
    }
}
