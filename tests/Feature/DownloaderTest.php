<?php

use Farzai\Geonames\Downloader\GazetteerDownloader;
use Farzai\Geonames\Downloader\GeonamesDownloader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    // Create test data if it doesn't exist
    if (! file_exists(__DIR__.'/../stubs/TH.zip')) {
        require __DIR__.'/../stubs/create_test_data.php';
    }
});

test('postal codes downloader can download country data', function () {
    $zipContent = file_get_contents(__DIR__.'/../stubs/TH.zip');

    // Create a mock response
    $mock = new MockHandler([
        new Response(200, ['Content-Length' => strlen($zipContent)], $zipContent),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $downloader = new GeonamesDownloader($client);
    $downloader->download('TH', $this->getTestDataPath());

    expect(file_exists($this->getTestDataPath('TH.zip')))->toBeTrue();
});

test('gazetteer downloader can download country data and admin codes', function () {
    $zipContent = file_get_contents(__DIR__.'/../stubs/TH_gaz.zip');
    $admin1Content = file_get_contents(__DIR__.'/../stubs/admin1CodesASCII.txt');
    $admin2Content = file_get_contents(__DIR__.'/../stubs/admin2Codes.txt');

    // Create mock responses for country data and admin codes
    $mock = new MockHandler([
        new Response(200, ['Content-Length' => strlen($zipContent)], $zipContent),
        new Response(200, ['Content-Length' => strlen($admin1Content)], $admin1Content),
        new Response(200, ['Content-Length' => strlen($admin2Content)], $admin2Content),
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $downloader = new GazetteerDownloader($client);
    $downloader->download('TH', $this->getTestDataPath());

    expect(file_exists($this->getTestDataPath('TH.zip')))->toBeTrue()
        ->and(file_exists($this->getTestDataPath('admin1CodesASCII.txt')))->toBeTrue()
        ->and(file_exists($this->getTestDataPath('admin2Codes.txt')))->toBeTrue();
});
