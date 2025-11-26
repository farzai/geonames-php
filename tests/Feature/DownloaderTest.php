<?php

use Farzai\Geonames\Downloader\GazetteerDownloader;
use Farzai\Geonames\Downloader\GeonamesDownloader;
use Farzai\Geonames\Tests\Helpers\MockHttpClient;

beforeEach(function () {
    // Create test data if it doesn't exist
    if (! file_exists(__DIR__.'/../stubs/TH.zip')) {
        require __DIR__.'/../stubs/create_test_data.php';
    }
});

test('postal codes downloader can download country data', function () {
    $zipContent = file_get_contents(__DIR__.'/../stubs/TH.zip');

    $transport = MockHttpClient::createTransport([
        ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
    ]);

    $downloader = new GeonamesDownloader($transport);
    $downloader->download('TH', $this->getTestDataPath());

    expect(file_exists($this->getTestDataPath('TH.zip')))->toBeTrue();
});

test('gazetteer downloader can download country data and admin codes', function () {
    $zipContent = file_get_contents(__DIR__.'/../stubs/TH_gaz.zip');
    $admin1Content = file_get_contents(__DIR__.'/../stubs/admin1CodesASCII.txt');
    $admin2Content = file_get_contents(__DIR__.'/../stubs/admin2Codes.txt');

    $transport = MockHttpClient::createTransport([
        ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
        ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
        ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
    ]);

    $downloader = new GazetteerDownloader($transport);
    $downloader->download('TH', $this->getTestDataPath());

    expect(file_exists($this->getTestDataPath('TH.zip')))->toBeTrue()
        ->and(file_exists($this->getTestDataPath('admin1CodesASCII.txt')))->toBeTrue()
        ->and(file_exists($this->getTestDataPath('admin2Codes.txt')))->toBeTrue();
});

test('gazetteer downloader downloadAll works', function () {
    $zipContent = file_get_contents(__DIR__.'/../stubs/TH_gaz.zip');
    $admin1Content = file_get_contents(__DIR__.'/../stubs/admin1CodesASCII.txt');
    $admin2Content = file_get_contents(__DIR__.'/../stubs/admin2Codes.txt');

    $transport = MockHttpClient::createTransport([
        ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
        ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
        ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
    ]);

    $downloader = new GazetteerDownloader($transport);
    $downloader->downloadAll($this->getTestDataPath());

    expect(file_exists($this->getTestDataPath('allCountries.zip')))->toBeTrue()
        ->and(file_exists($this->getTestDataPath('admin1CodesASCII.txt')))->toBeTrue()
        ->and(file_exists($this->getTestDataPath('admin2Codes.txt')))->toBeTrue();
});

test('postal downloader downloadAll works', function () {
    $zipContent = file_get_contents(__DIR__.'/../stubs/TH.zip');

    $transport = MockHttpClient::createTransport([
        ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
    ]);

    $downloader = new GeonamesDownloader($transport);
    $downloader->downloadAll($this->getTestDataPath());

    expect(file_exists($this->getTestDataPath('allCountries.zip')))->toBeTrue();
});

test('downloader handles empty Content-Length header', function () {
    $zipContent = file_get_contents(__DIR__.'/../stubs/TH.zip');

    $transport = MockHttpClient::createTransport([
        ['content' => $zipContent, 'headers' => []],
    ]);

    $downloader = new GeonamesDownloader($transport);
    $downloader->download('TH', $this->getTestDataPath());

    expect(file_exists($this->getTestDataPath('TH.zip')))->toBeTrue();
});
