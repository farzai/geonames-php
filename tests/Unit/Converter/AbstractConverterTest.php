<?php

use Farzai\Geonames\Converter\AbstractConverter;
use Farzai\Geonames\Exceptions\GeonamesException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Concrete implementation of AbstractConverter for testing purposes.
 */
class TestableConverter extends AbstractConverter
{
    /**
     * Track which files were processed.
     *
     * @var array<int, array{txt: string, output: string}>
     */
    public array $processedFiles = [];

    protected function processFile(string $txtFile, string $outputFile): void
    {
        $this->processedFiles[] = ['txt' => $txtFile, 'output' => $outputFile];
    }

    // Expose protected methods for testing
    public function testExtractZipFile(string $zipFile): string
    {
        return $this->extractZipFile($zipFile);
    }

    public function testFindDataFile(string $directory): string
    {
        return $this->findDataFile($directory);
    }

    public function testCleanupTempDirectory(string $tempDir): void
    {
        $this->cleanupTempDirectory($tempDir);
    }

    public function testCountLines(string $file): int
    {
        return $this->countLines($file);
    }

    public function testStreamPostalCodeRecords(string $txtFile): \Generator
    {
        return $this->streamPostalCodeRecords($txtFile);
    }

    public function testCreateProgressBar(int $totalLines): ?ProgressBar
    {
        return $this->createProgressBar($totalLines);
    }
}

describe('AbstractConverter', function () {
    beforeEach(function () {
        // Create test data if it doesn't exist
        if (! file_exists(__DIR__.'/../../stubs/TH.zip')) {
            require __DIR__.'/../../stubs/create_test_data.php';
        }
    });

    describe('setOutput', function () {
        it('returns self for method chaining', function () {
            $converter = new TestableConverter;
            $output = new NullOutput;

            $result = $converter->setOutput($output);

            expect($result)->toBe($converter);
        });
    });

    describe('convert', function () {
        it('extracts zip, processes file, and cleans up', function () {
            copy(__DIR__.'/../../stubs/TH.zip', $this->getTestDataPath('TH.zip'));

            $converter = new TestableConverter;
            $converter->convert(
                $this->getTestDataPath('TH.zip'),
                $this->getTestDataPath('output.json')
            );

            expect($converter->processedFiles)->toHaveCount(1);
        });
    });

    describe('extractZipFile', function () {
        it('extracts zip to temp directory', function () {
            copy(__DIR__.'/../../stubs/TH.zip', $this->getTestDataPath('TH.zip'));

            $converter = new TestableConverter;
            $tempDir = $converter->testExtractZipFile($this->getTestDataPath('TH.zip'));

            expect(is_dir($tempDir))->toBeTrue();
            expect(glob($tempDir.'/*.txt'))->not->toBeEmpty();

            // Cleanup
            $converter->testCleanupTempDirectory($tempDir);
        });

        it('throws exception for invalid zip file', function () {
            file_put_contents($this->getTestDataPath('invalid.zip'), 'not a zip');

            $converter = new TestableConverter;
            $converter->testExtractZipFile($this->getTestDataPath('invalid.zip'));
        })->throws(GeonamesException::class);
    });

    describe('findDataFile', function () {
        it('finds txt file in directory', function () {
            $tempDir = $this->getTestDataPath('temp_find');
            mkdir($tempDir);
            file_put_contents($tempDir.'/data.txt', 'test');

            $converter = new TestableConverter;
            $result = $converter->testFindDataFile($tempDir);

            expect($result)->toEndWith('data.txt');
        });

        it('throws exception when no txt file found', function () {
            $tempDir = $this->getTestDataPath('empty_dir');
            mkdir($tempDir);

            $converter = new TestableConverter;
            $converter->testFindDataFile($tempDir);
        })->throws(GeonamesException::class);
    });

    describe('cleanupTempDirectory', function () {
        it('removes directory and contents', function () {
            $tempDir = $this->getTestDataPath('cleanup_test');
            mkdir($tempDir);
            file_put_contents($tempDir.'/file.txt', 'test');

            $converter = new TestableConverter;
            $converter->testCleanupTempDirectory($tempDir);

            expect(is_dir($tempDir))->toBeFalse();
        });

        it('handles non-existent directory gracefully', function () {
            $converter = new TestableConverter;
            $converter->testCleanupTempDirectory($this->getTestDataPath('nonexistent_path'));

            expect(true)->toBeTrue(); // No exception thrown
        });
    });

    describe('countLines', function () {
        it('counts lines in file correctly', function () {
            $file = $this->getTestDataPath('count_test.txt');
            file_put_contents($file, "line1\nline2\nline3\n");

            $converter = new TestableConverter;
            $count = $converter->testCountLines($file);

            expect($count)->toBe(3);
        });

        it('throws exception for unreadable file', function () {
            $converter = new TestableConverter;

            // Suppress PHP warning from fopen before it throws exception
            set_error_handler(fn () => true);
            try {
                $converter->testCountLines('/nonexistent/file.txt');
            } finally {
                restore_error_handler();
            }
        })->throws(GeonamesException::class);
    });

    describe('streamPostalCodeRecords', function () {
        it('yields parsed postal code records', function () {
            copy(__DIR__.'/../../stubs/postal_codes.txt', $this->getTestDataPath('postal.txt'));

            $converter = new TestableConverter;
            $records = iterator_to_array($converter->testStreamPostalCodeRecords($this->getTestDataPath('postal.txt')));

            expect($records)->not->toBeEmpty();
            expect($records[0])->toHaveKeys([
                'country_code',
                'postal_code',
                'place_name',
                'admin_name1',
                'admin_code1',
                'latitude',
                'longitude',
            ]);
        });

        it('skips lines with insufficient fields', function () {
            $file = $this->getTestDataPath('bad_postal.txt');
            file_put_contents($file, "TH\t10200\n");

            $converter = new TestableConverter;
            $records = iterator_to_array($converter->testStreamPostalCodeRecords($file));

            expect($records)->toBeEmpty();
        });
    });

    describe('createProgressBar', function () {
        it('returns null when no output set', function () {
            $converter = new TestableConverter;

            $result = $converter->testCreateProgressBar(100);

            expect($result)->toBeNull();
        });

        it('returns ProgressBar when output is set', function () {
            $converter = new TestableConverter;
            $converter->setOutput(new BufferedOutput);

            $result = $converter->testCreateProgressBar(100);

            expect($result)->toBeInstanceOf(ProgressBar::class);
        });
    });
});
