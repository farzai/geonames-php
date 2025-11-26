<?php

use Farzai\Geonames\Downloader\GazetteerDownloader;
use Farzai\Geonames\Tests\Helpers\MockHttpClient;
use Symfony\Component\Console\Output\BufferedOutput;

describe('GazetteerDownloader', function () {
    beforeEach(function () {
        if (! file_exists(__DIR__.'/../../stubs/TH_gaz.zip')) {
            require __DIR__.'/../../stubs/create_test_data.php';
        }
    });

    describe('download', function () {
        it('downloads country zip file', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $downloader->download('TH', $this->getTestDataPath());

            expect(file_exists($this->getTestDataPath('TH.zip')))->toBeTrue();
        });

        it('uppercases lowercase country code', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $downloader->download('th', $this->getTestDataPath());

            // File is saved as uppercase
            expect(file_exists($this->getTestDataPath('TH.zip')))->toBeTrue();
        });

        it('downloads admin code files', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $downloader->download('TH', $this->getTestDataPath());

            expect(file_exists($this->getTestDataPath('admin1CodesASCII.txt')))->toBeTrue();
            expect(file_exists($this->getTestDataPath('admin2Codes.txt')))->toBeTrue();
        });

        it('outputs download messages when output set', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $output = new BufferedOutput;
            $downloader = new GazetteerDownloader($transport);
            $downloader->setOutput($output);
            $downloader->download('TH', $this->getTestDataPath());

            $display = $output->fetch();
            expect($display)->toContain('admin1CodesASCII.txt');
            expect($display)->toContain('admin2Codes.txt');
        });

        it('saves admin code files with correct content', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $downloader->download('TH', $this->getTestDataPath());

            expect(file_get_contents($this->getTestDataPath('admin1CodesASCII.txt')))->toBe($admin1Content);
            expect(file_get_contents($this->getTestDataPath('admin2Codes.txt')))->toBe($admin2Content);
        });
    });

    describe('downloadAll', function () {
        it('downloads allCountries.zip', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $downloader->downloadAll($this->getTestDataPath());

            expect(file_exists($this->getTestDataPath('allCountries.zip')))->toBeTrue();
        });

        it('also downloads admin codes', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $downloader->downloadAll($this->getTestDataPath());

            expect(file_exists($this->getTestDataPath('admin1CodesASCII.txt')))->toBeTrue();
            expect(file_exists($this->getTestDataPath('admin2Codes.txt')))->toBeTrue();
        });

        it('shows progress when output set', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $output = new BufferedOutput;
            $downloader = new GazetteerDownloader($transport);
            $downloader->setOutput($output);
            $downloader->downloadAll($this->getTestDataPath());

            $display = $output->fetch();
            expect($display)->toContain('%');
        });
    });

    describe('setOutput', function () {
        it('returns self for method chaining', function () {
            $transport = MockHttpClient::createTransport([]);
            $downloader = new GazetteerDownloader($transport);

            $result = $downloader->setOutput(new BufferedOutput);

            expect($result)->toBe($downloader);
        });
    });
});
