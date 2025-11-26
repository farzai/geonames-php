<?php

use Farzai\Geonames\Console\Commands\DownloadGazetteerCommand;
use Farzai\Geonames\Converter\GazetteerConverter;
use Farzai\Geonames\Downloader\GazetteerDownloader;
use Farzai\Geonames\Tests\Helpers\MockHttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function () {
    // Create test data if it doesn't exist
    if (! file_exists(__DIR__.'/../../stubs/TH_gaz.zip')) {
        require __DIR__.'/../../stubs/create_test_data.php';
    }
});

describe('DownloadGazetteerCommand', function () {
    describe('configure', function () {
        it('has correct command name', function () {
            $command = new DownloadGazetteerCommand;

            expect($command->getName())->toBe('geonames:gazetteer:download');
        });

        it('has country argument', function () {
            $command = new DownloadGazetteerCommand;
            $definition = $command->getDefinition();

            expect($definition->hasArgument('country'))->toBeTrue();
        });

        it('has output option with default', function () {
            $command = new DownloadGazetteerCommand;
            $option = $command->getDefinition()->getOption('output');

            expect($option)->not->toBeNull();
            expect($option->getDefault())->toContain('/data');
        });

        it('has format option defaulting to json', function () {
            $command = new DownloadGazetteerCommand;
            $option = $command->getDefinition()->getOption('format');

            expect($option)->not->toBeNull();
            expect($option->getDefault())->toBe('json');
        });

        it('has feature-class option defaulting to P', function () {
            $command = new DownloadGazetteerCommand;
            $option = $command->getDefinition()->getOption('feature-class');

            expect($option)->not->toBeNull();
            expect($option->getDefault())->toBe('P');
        });

        it('has mongodb options', function () {
            $command = new DownloadGazetteerCommand;
            $definition = $command->getDefinition();

            expect($definition->hasOption('mongodb-uri'))->toBeTrue();
            expect($definition->hasOption('mongodb-db'))->toBeTrue();
            expect($definition->hasOption('mongodb-collection'))->toBeTrue();
        });
    });

    describe('execute with json format', function () {
        it('downloads and converts to JSON successfully', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $converter = new GazetteerConverter;

            $command = new DownloadGazetteerCommand($downloader, $converter);
            $tester = new CommandTester($command);

            $tester->execute([
                'country' => 'TH',
                '--output' => $this->getTestDataPath(),
            ]);

            expect($tester->getStatusCode())->toBe(Command::SUCCESS);
            expect($tester->getDisplay())->toContain('successfully');
            expect(file_exists($this->getTestDataPath('TH.json')))->toBeTrue();
        });

        it('cleans up admin code files after processing', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $converter = new GazetteerConverter;

            $command = new DownloadGazetteerCommand($downloader, $converter);
            $tester = new CommandTester($command);

            $tester->execute([
                'country' => 'TH',
                '--output' => $this->getTestDataPath(),
            ]);

            // Admin code files should be cleaned up
            expect(file_exists($this->getTestDataPath('admin1CodesASCII.txt')))->toBeFalse();
            expect(file_exists($this->getTestDataPath('admin2Codes.txt')))->toBeFalse();
            // ZIP file should be cleaned up
            expect(file_exists($this->getTestDataPath('TH.zip')))->toBeFalse();
        });
    });

    describe('execute with unsupported format', function () {
        it('returns failure for unknown format', function () {
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $converter = new GazetteerConverter;

            $command = new DownloadGazetteerCommand($downloader, $converter);
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
            $zipContent = file_get_contents(__DIR__.'/../../stubs/TH_gaz.zip');
            $admin1Content = file_get_contents(__DIR__.'/../../stubs/admin1CodesASCII.txt');
            $admin2Content = file_get_contents(__DIR__.'/../../stubs/admin2Codes.txt');

            $transport = MockHttpClient::createTransport([
                ['content' => $zipContent, 'headers' => ['Content-Length' => [(string) strlen($zipContent)]]],
                ['content' => $admin1Content, 'headers' => ['Content-Length' => [(string) strlen($admin1Content)]]],
                ['content' => $admin2Content, 'headers' => ['Content-Length' => [(string) strlen($admin2Content)]]],
            ]);

            $downloader = new GazetteerDownloader($transport);
            $converter = new GazetteerConverter;

            $command = new DownloadGazetteerCommand($downloader, $converter);
            $tester = new CommandTester($command);

            $newDir = $this->getTestDataPath('nested/gaz_output');

            $tester->execute([
                'country' => 'TH',
                '--output' => $newDir,
            ]);

            expect($tester->getStatusCode())->toBe(Command::SUCCESS);
            expect(is_dir($newDir))->toBeTrue();
        });
    });
});
