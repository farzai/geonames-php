<?php

use Farzai\Geonames\Downloader\GeonamesDownloader;
use Farzai\Geonames\Tests\Helpers\MockHttpClient;
use Symfony\Component\Console\Output\BufferedOutput;

describe('GeonamesDownloader', function () {
    beforeEach(function () {
        if (! file_exists(__DIR__.'/../../stubs/TH.zip')) {
            require __DIR__.'/../../stubs/create_test_data.php';
        }
    });

    describe('download', function () {
        it('downloads country zip file', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $downloader = new GeonamesDownloader($transport);
            $downloader->download('US', $this->getTestDataPath());

            expect(file_exists($this->getTestDataPath('US.zip')))->toBeTrue();
        });

        it('uppercases lowercase country code', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $downloader = new GeonamesDownloader($transport);
            $downloader->download('us', $this->getTestDataPath());

            expect(file_exists($this->getTestDataPath('US.zip')))->toBeTrue();
        });

        it('saves to correct destination', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $downloader = new GeonamesDownloader($transport);
            mkdir($this->getTestDataPath('subdir'));
            $downloader->download('TH', $this->getTestDataPath('subdir'));

            expect(file_exists($this->getTestDataPath('subdir/TH.zip')))->toBeTrue();
        });

        it('saves correct file content', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $downloader = new GeonamesDownloader($transport);
            $downloader->download('TH', $this->getTestDataPath());

            expect(file_get_contents($this->getTestDataPath('TH.zip')))->toBe($zipContent);
        });

        it('shows progress when output set', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $output = new BufferedOutput;
            $downloader = new GeonamesDownloader($transport);
            $downloader->setOutput($output);
            $downloader->download('TH', $this->getTestDataPath());

            $display = $output->fetch();
            expect($display)->toContain('%');
        });
    });

    describe('downloadAll', function () {
        it('downloads allCountries.zip', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $downloader = new GeonamesDownloader($transport);
            $downloader->downloadAll($this->getTestDataPath());

            expect(file_exists($this->getTestDataPath('allCountries.zip')))->toBeTrue();
        });

        it('saves correct file content', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $downloader = new GeonamesDownloader($transport);
            $downloader->downloadAll($this->getTestDataPath());

            expect(file_get_contents($this->getTestDataPath('allCountries.zip')))->toBe($zipContent);
        });

        it('shows progress when output set', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
            ]);

            $output = new BufferedOutput;
            $downloader = new GeonamesDownloader($transport);
            $downloader->setOutput($output);
            $downloader->downloadAll($this->getTestDataPath());

            $display = $output->fetch();
            expect($display)->toContain('%');
        });
    });

    describe('setOutput', function () {
        it('returns self for method chaining', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new GeonamesDownloader($transport);

            $result = $downloader->setOutput(new BufferedOutput);

            expect($result)->toBe($downloader);
        });
    });
});
