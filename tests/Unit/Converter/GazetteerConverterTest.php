<?php

use Farzai\Geonames\Converter\GazetteerConverter;
use Farzai\Geonames\Exceptions\GeonamesException;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Testable wrapper for GazetteerConverter that exposes protected methods.
 */
class TestableGazetteerJsonConverter extends GazetteerConverter
{
    public function testProcessFile(string $txtFile, string $outputFile): void
    {
        $this->processFile($txtFile, $outputFile);
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public function testStreamGazetteerRecords(string $txtFile): \Generator
    {
        return $this->streamGazetteerRecords($txtFile);
    }

    public function testLoadAdminCodes(string $adminCodesDir): void
    {
        $this->loadAdminCodes($adminCodesDir);
    }
}

describe('GazetteerConverter', function () {
    beforeEach(function () {
        // Create test data if it doesn't exist
        if (! file_exists(__DIR__.'/../../stubs/TH_gaz.zip')) {
            require __DIR__.'/../../stubs/create_test_data.php';
        }
    });

    describe('processFile', function () {
        it('creates valid JSON array output', function () {
            copy(__DIR__.'/../../stubs/gazetteer.txt', $this->getTestDataPath('gazetteer.txt'));

            $converter = new TestableGazetteerJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('gazetteer.txt'),
                $this->getTestDataPath('output.json')
            );

            $content = file_get_contents($this->getTestDataPath('output.json'));
            $data = json_decode($content, true);

            expect($data)->toBeArray();
            expect(json_last_error())->toBe(JSON_ERROR_NONE);
        });

        it('writes correct JSON structure with all gazetteer fields', function () {
            copy(__DIR__.'/../../stubs/gazetteer.txt', $this->getTestDataPath('gazetteer.txt'));
            copy(__DIR__.'/../../stubs/admin1CodesASCII.txt', $this->getTestDataPath('admin1CodesASCII.txt'));
            copy(__DIR__.'/../../stubs/admin2Codes.txt', $this->getTestDataPath('admin2Codes.txt'));

            $converter = new TestableGazetteerJsonConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());
            $converter->testProcessFile(
                $this->getTestDataPath('gazetteer.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);

            expect($data)->not->toBeEmpty();
            expect($data[0])->toHaveKeys([
                'geoname_id',
                'name',
                'ascii_name',
                'alternate_names',
                'latitude',
                'longitude',
                'feature_class',
                'feature_code',
                'country_code',
                'admin1_code',
                'admin2_code',
                'timezone',
                'modification_date',
            ]);
        });

        it('handles empty file', function () {
            file_put_contents($this->getTestDataPath('empty.txt'), '');

            $converter = new TestableGazetteerJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('empty.txt'),
                $this->getTestDataPath('output.json')
            );

            $content = file_get_contents($this->getTestDataPath('output.json'));
            expect($content)->toBe('[]');
        });

        it('handles single record', function () {
            $line = "1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12\n";
            file_put_contents($this->getTestDataPath('single.txt'), $line);

            $converter = new TestableGazetteerJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('single.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect($data)->toHaveCount(1);
            expect($data[0]['name'])->toBe('Bangkok');
        });

        it('throws on unwritable output path', function () {
            copy(__DIR__.'/../../stubs/gazetteer.txt', $this->getTestDataPath('gazetteer.txt'));

            $converter = new TestableGazetteerJsonConverter;

            set_error_handler(fn () => true);
            try {
                $converter->testProcessFile(
                    $this->getTestDataPath('gazetteer.txt'),
                    '/nonexistent/path/output.json'
                );
            } finally {
                restore_error_handler();
            }
        })->throws(GeonamesException::class);

        it('updates progress bar when output is set', function () {
            copy(__DIR__.'/../../stubs/gazetteer.txt', $this->getTestDataPath('gazetteer.txt'));

            $output = new BufferedOutput;
            $converter = new TestableGazetteerJsonConverter;
            $converter->setOutput($output);
            $converter->testProcessFile(
                $this->getTestDataPath('gazetteer.txt'),
                $this->getTestDataPath('output.json')
            );

            $display = $output->fetch();
            expect($display)->toContain('%');
        });

        it('handles Unicode characters in names', function () {
            $line = "1609350\tกรุงเทพ\tBangkok\tКрунг Тхеп,曼谷\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12\n";
            file_put_contents($this->getTestDataPath('unicode.txt'), $line);

            $converter = new TestableGazetteerJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('unicode.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect($data[0]['name'])->toBe('กรุงเทพ');
            expect($data[0]['alternate_names'])->toContain('Крунг Тхеп');
            expect($data[0]['alternate_names'])->toContain('曼谷');
        });

        it('processes multiple records correctly', function () {
            copy(__DIR__.'/../../stubs/gazetteer.txt', $this->getTestDataPath('gazetteer.txt'));

            $converter = new TestableGazetteerJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('gazetteer.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect(count($data))->toBeGreaterThanOrEqual(1);
        });
    });

    describe('streamGazetteerRecords', function () {
        it('yields correct records from file', function () {
            copy(__DIR__.'/../../stubs/gazetteer.txt', $this->getTestDataPath('gazetteer.txt'));

            $converter = new TestableGazetteerJsonConverter;
            $records = iterator_to_array($converter->testStreamGazetteerRecords($this->getTestDataPath('gazetteer.txt')));

            expect($records)->not->toBeEmpty();
            expect($records[0])->toHaveKey('geoname_id');
            expect($records[0])->toHaveKey('name');
            expect($records[0])->toHaveKey('latitude');
            expect($records[0])->toHaveKey('longitude');
        });

        it('skips empty lines', function () {
            $content = "\n\n1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12\n\n";
            file_put_contents($this->getTestDataPath('with_blanks.txt'), $content);

            $converter = new TestableGazetteerJsonConverter;
            $records = iterator_to_array($converter->testStreamGazetteerRecords($this->getTestDataPath('with_blanks.txt')));

            expect($records)->toHaveCount(1);
        });

        it('throws on unreadable file', function () {
            $converter = new TestableGazetteerJsonConverter;

            set_error_handler(fn () => true);
            try {
                iterator_to_array($converter->testStreamGazetteerRecords('/nonexistent/file.txt'));
            } finally {
                restore_error_handler();
            }
        })->throws(GeonamesException::class);

        it('yields records with correct latitude and longitude types', function () {
            $line = "1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12\n";
            file_put_contents($this->getTestDataPath('coords.txt'), $line);

            $converter = new TestableGazetteerJsonConverter;
            $records = iterator_to_array($converter->testStreamGazetteerRecords($this->getTestDataPath('coords.txt')));

            expect($records[0]['latitude'])->toBe(13.75);
            expect($records[0]['longitude'])->toBe(100.51667);
            expect($records[0]['latitude'])->toBeFloat();
            expect($records[0]['longitude'])->toBeFloat();
        });

        it('handles records with empty optional fields', function () {
            // Record with empty cc2, admin3, admin4
            $line = "1609350\tBangkok\tBangkok\t\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t\t\tAsia/Bangkok\t2023-01-12\n";
            file_put_contents($this->getTestDataPath('empty_optional.txt'), $line);

            $converter = new TestableGazetteerJsonConverter;
            $records = iterator_to_array($converter->testStreamGazetteerRecords($this->getTestDataPath('empty_optional.txt')));

            expect($records[0]['alternate_names'])->toBeArray()->toBeEmpty();
        });
    });
});
