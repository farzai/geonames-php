<?php

use Farzai\Geonames\Console\Commands\DownloadPostalCodesCommand;
use Farzai\Geonames\Converter\PostalCodeConverter;
use Farzai\Geonames\Downloader\GeonamesDownloader;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    // Create test data if it doesn't exist
    if (! file_exists(__DIR__.'/../../stubs/TH.zip')) {
        require __DIR__.'/../../stubs/create_test_data.php';
    }
});

describe('DownloadPostalCodesCommand', function () {
    describe('configure', function () {
        it('has default name static property', function () {
            // Verify the class has the correct default name property
            $reflection = new ReflectionClass(DownloadPostalCodesCommand::class);
            $property = $reflection->getProperty('defaultName');

            expect($property->getValue())->toBe('geonames:download');
        });

        it('has country argument', function () {
            $command = new DownloadPostalCodesCommand;
            $definition = $command->getDefinition();

            expect($definition->hasArgument('country'))->toBeTrue();
        });

        it('has output option with default', function () {
            $command = new DownloadPostalCodesCommand;
            $option = $command->getDefinition()->getOption('output');

            expect($option)->not->toBeNull();
            expect($option->getDefault())->toContain('/data');
        });

        it('has format option defaulting to json', function () {
            $command = new DownloadPostalCodesCommand;
            $option = $command->getDefinition()->getOption('format');

            expect($option)->not->toBeNull();
            expect($option->getDefault())->toBe('json');
        });

        it('has mongodb options', function () {
            $command = new DownloadPostalCodesCommand;
            $definition = $command->getDefinition();

            expect($definition->hasOption('mongodb-uri'))->toBeTrue();
            expect($definition->hasOption('mongodb-db'))->toBeTrue();
            expect($definition->hasOption('mongodb-collection'))->toBeTrue();
        });
    });

    describe('execute with json format', function () {
        it('downloads and converts to JSON successfully', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            // Create mock HTTP client
            $mock = new MockHandler([
                new Response(200, ['Content-Length' => strlen($zipContent)], $zipContent),
            ]);
            $client = new Client(['handler' => HandlerStack::create($mock)]);

            // Create real downloader with mocked client and real converter
            $downloader = new GeonamesDownloader($client);
            $converter = new PostalCodeConverter;

            $command = new DownloadPostalCodesCommand($downloader, $converter);
            $tester = new CommandTester($command);

            $tester->execute([
                'country' => 'TH',
                '--output' => $this->getTestDataPath(),
            ]);

            expect($tester->getStatusCode())->toBe(Command::SUCCESS);
            expect($tester->getDisplay())->toContain('successfully');
            expect(file_exists($this->getTestDataPath('TH.json')))->toBeTrue();
        });

        it('downloads all countries when specified', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $mock = new MockHandler([
                new Response(200, ['Content-Length' => strlen($zipContent)], $zipContent),
            ]);
            $client = new Client(['handler' => HandlerStack::create($mock)]);

            $downloader = new GeonamesDownloader($client);
            $converter = new PostalCodeConverter;

            $command = new DownloadPostalCodesCommand($downloader, $converter);
            $tester = new CommandTester($command);

            $tester->execute([
                'country' => 'all',
                '--output' => $this->getTestDataPath(),
            ]);

            expect($tester->getStatusCode())->toBe(Command::SUCCESS);
        });
    });

    describe('execute with unsupported format', function () {
        it('returns failure for unknown format', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $mock = new MockHandler([
                new Response(200, ['Content-Length' => strlen($zipContent)], $zipContent),
            ]);
            $client = new Client(['handler' => HandlerStack::create($mock)]);

            $downloader = new GeonamesDownloader($client);
            $converter = new PostalCodeConverter;

            $command = new DownloadPostalCodesCommand($downloader, $converter);
            $tester = new CommandTester($command);

            $tester->execute([
                'country' => 'TH',
                '--output' => $this->getTestDataPath(),
                '--format' => 'xml',
            ]);

            expect($tester->getStatusCode())->toBe(Command::FAILURE);
            expect($tester->getDisplay())->toContain('Unsupported format');
        });
    });

    describe('execute creates output directory', function () {
        it('creates output directory if it does not exist', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH.zip');

            $mock = new MockHandler([
                new Response(200, ['Content-Length' => strlen($zipContent)], $zipContent),
            ]);
            $client = new Client(['handler' => HandlerStack::create($mock)]);

            $downloader = new GeonamesDownloader($client);
            $converter = new PostalCodeConverter;

            $command = new DownloadPostalCodesCommand($downloader, $converter);
            $tester = new CommandTester($command);

            $newDir = $this->getTestDataPath('nested/output');

            $tester->execute([
                'country' => 'TH',
                '--output' => $newDir,
            ]);

            expect($tester->getStatusCode())->toBe(Command::SUCCESS);
            expect(is_dir($newDir))->toBeTrue();
        });
    });
});
