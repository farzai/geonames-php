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

    /** A fixed path for the extraction, so a test can plant something there. */
    public ?string $forcedTempPath = null;

    protected function newTempDirectoryPath(): string
    {
        return $this->forcedTempPath ?? parent::newTempDirectoryPath();
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

    public function testStreamPostalCodeRecords(string $txtFile): Generator
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

        // #49: another user on a shared host must not be able to pick where
        // extractTo writes by creating the directory first.
        it('never extracts into a symlink planted at the target path', function () {
            copy(__DIR__.'/../../stubs/TH.zip', $this->getTestDataPath('TH.zip'));
            $victim = $this->getTestDataPath('victim');
            mkdir($victim);
            $planted = $this->getTestDataPath('planted');
            symlink($victim, $planted);

            $converter = new TestableConverter;
            $converter->forcedTempPath = $planted;

            expect(fn () => $converter->testExtractZipFile($this->getTestDataPath('TH.zip')))
                ->toThrow(GeonamesException::class);
            expect(glob($victim.'/*'))->toBe([]);
        });

        it('refuses a directory that already exists at the target path', function () {
            copy(__DIR__.'/../../stubs/TH.zip', $this->getTestDataPath('TH.zip'));
            $existing = $this->getTestDataPath('existing');
            mkdir($existing);

            $converter = new TestableConverter;
            $converter->forcedTempPath = $existing;

            expect(fn () => $converter->testExtractZipFile($this->getTestDataPath('TH.zip')))
                ->toThrow(GeonamesException::class);
            expect(glob($existing.'/*'))->toBe([]);
        });

        it('extracts into a fresh random directory only the owner can enter', function () {
            copy(__DIR__.'/../../stubs/TH.zip', $this->getTestDataPath('TH.zip'));

            $converter = new TestableConverter;
            $tempDir = $converter->testExtractZipFile($this->getTestDataPath('TH.zip'));

            try {
                expect(basename($tempDir))->toMatch('/^geonames_[0-9a-f]{32}$/');
                expect(is_link($tempDir))->toBeFalse();
                expect(fileperms($tempDir) & 0777)->toBe(0700);
            } finally {
                $converter->testCleanupTempDirectory($tempDir);
            }
        });

        it('does not repeat a directory name', function () {
            copy(__DIR__.'/../../stubs/TH.zip', $this->getTestDataPath('TH.zip'));
            $converter = new TestableConverter;

            $first = $converter->testExtractZipFile($this->getTestDataPath('TH.zip'));
            $second = $converter->testExtractZipFile($this->getTestDataPath('TH.zip'));

            try {
                expect($second)->not->toBe($first);
            } finally {
                $converter->testCleanupTempDirectory($first);
                $converter->testCleanupTempDirectory($second);
            }
        });

        it('says why an invalid zip file could not be opened', function () {
            file_put_contents($this->getTestDataPath('invalid.zip'), 'not a zip');

            $converter = new TestableConverter;

            expect(fn () => $converter->testExtractZipFile($this->getTestDataPath('invalid.zip')))
                ->toThrow(GeonamesException::class, 'not a ZIP archive');
        });
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
        // Review of #50: a subdirectory made unlink warn and rmdir fail, which
        // left the extracted files in the 0700 temp directory.
        it('removes nested directories, and a link as a link', function () {
            $tempDir = $this->getTestDataPath('cleanup_nested');
            mkdir($tempDir.'/a/b', 0777, true);
            file_put_contents($tempDir.'/a/b/deep.txt', 'x');
            file_put_contents($tempDir.'/top.txt', 'x');
            $outside = $this->getTestDataPath('outside');
            mkdir($outside);
            file_put_contents($outside.'/keep.txt', 'keep');
            symlink($outside, $tempDir.'/link');

            (new TestableConverter)->testCleanupTempDirectory($tempDir);

            expect(file_exists($tempDir))->toBeFalse();
            expect(file_get_contents($outside.'/keep.txt'))->toBe('keep');
        });

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

        it('handles empty file', function () {
            $file = $this->getTestDataPath('empty.txt');
            file_put_contents($file, '');

            $converter = new TestableConverter;
            $count = $converter->testCountLines($file);

            expect($count)->toBe(0);
        });

        it('handles file without trailing newline', function () {
            $file = $this->getTestDataPath('no_newline.txt');
            file_put_contents($file, "line1\nline2");

            $converter = new TestableConverter;
            $count = $converter->testCountLines($file);

            // Only counts \n characters
            expect($count)->toBe(1);
        });

        it('handles file with only newlines', function () {
            $file = $this->getTestDataPath('only_newlines.txt');
            file_put_contents($file, "\n\n\n");

            $converter = new TestableConverter;
            $count = $converter->testCountLines($file);

            expect($count)->toBe(3);
        });
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

        it('handles UTF-8 place names', function () {
            $line = "TH\t10200\tกรุงเทพ\tBangkok\t10\t\t\t\t\t13.7235\t100.5147\t1\n";
            file_put_contents($this->getTestDataPath('utf8.txt'), $line);

            $converter = new TestableConverter;
            $records = iterator_to_array($converter->testStreamPostalCodeRecords($this->getTestDataPath('utf8.txt')));

            expect($records[0]['place_name'])->toBe('กรุงเทพ');
        });

        it('handles empty lines in file', function () {
            $content = "\n\nTH\t10200\tBang Rak\tBangkok\t10\t\t\t\t\t13.7235\t100.5147\t1\n\n";
            file_put_contents($this->getTestDataPath('with_empty.txt'), $content);

            $converter = new TestableConverter;
            $records = iterator_to_array($converter->testStreamPostalCodeRecords($this->getTestDataPath('with_empty.txt')));

            expect($records)->toHaveCount(1);
        });

        it('parses numeric accuracy field', function () {
            $line = "TH\t10200\tBang Rak\tBangkok\t10\t\t\t\t\t13.7235\t100.5147\t4\n";
            file_put_contents($this->getTestDataPath('accuracy.txt'), $line);

            $converter = new TestableConverter;
            $records = iterator_to_array($converter->testStreamPostalCodeRecords($this->getTestDataPath('accuracy.txt')));

            expect($records[0]['accuracy'])->toBe(4);
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

        it('returns null when total lines is zero', function () {
            $converter = new TestableConverter;
            $converter->setOutput(new BufferedOutput);

            $result = $converter->testCreateProgressBar(0);

            // ProgressBar with zero max can cause issues in older Symfony versions
            expect($result)->toBeNull();
        });
    });
});
