<?php

use Farzai\Geonames\Converter\PostalCodeConverter;
use Farzai\Geonames\Exceptions\GeonamesException;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Testable wrapper for PostalCodeConverter that exposes protected methods.
 */
class TestablePostalCodeJsonConverter extends PostalCodeConverter
{
    public function testProcessFile(string $txtFile, string $outputFile): void
    {
        $this->processFile($txtFile, $outputFile);
    }
}

describe('PostalCodeConverter', function () {
    beforeEach(function () {
        // Create test data if it doesn't exist
        if (! file_exists(__DIR__.'/../../stubs/TH.zip')) {
            require __DIR__.'/../../stubs/create_test_data.php';
        }
    });

    describe('processFile', function () {
        it('creates valid JSON array output', function () {
            copy(__DIR__.'/../../stubs/postal_codes.txt', $this->getTestDataPath('postal.txt'));

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('postal.txt'),
                $this->getTestDataPath('output.json')
            );

            $content = file_get_contents($this->getTestDataPath('output.json'));
            $data = json_decode($content, true);

            expect($data)->toBeArray();
            expect(json_last_error())->toBe(JSON_ERROR_NONE);
        });

        it('writes correct JSON structure with postal code fields', function () {
            copy(__DIR__.'/../../stubs/postal_codes.txt', $this->getTestDataPath('postal.txt'));

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('postal.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);

            expect($data)->not->toBeEmpty();
            expect($data[0])->toHaveKeys([
                'country_code',
                'postal_code',
                'place_name',
                'admin_name1',
                'admin_code1',
                'latitude',
                'longitude',
            ]);
        });

        it('handles empty file', function () {
            file_put_contents($this->getTestDataPath('empty.txt'), '');

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('empty.txt'),
                $this->getTestDataPath('output.json')
            );

            $content = file_get_contents($this->getTestDataPath('output.json'));
            // Empty file produces "[\n\n]" because opening bracket + newline + closing bracket
            expect($content)->toBe("[\n\n]");
        });

        it('preserves latitude and longitude precision', function () {
            $line = "US\t90210\tBeverly Hills\tCalifornia\tCA\t\t\t\t\t34.0901234\t-118.4065432\t1\n";
            file_put_contents($this->getTestDataPath('precision.txt'), $line);

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('precision.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect($data[0]['latitude'])->toBe(34.0901234);
            expect($data[0]['longitude'])->toBe(-118.4065432);
        });

        it('handles empty latitude and longitude as zero', function () {
            // When lat/long fields exist but are empty, they become 0.0 (not null)
            $line = "US\t90210\tBeverly Hills\tCalifornia\tCA\t\t\t\t\t\t\t1\n";
            file_put_contents($this->getTestDataPath('empty_coords.txt'), $line);

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('empty_coords.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            // Empty string cast to float becomes 0.0, JSON decode may return as int 0
            expect($data[0]['latitude'])->toEqual(0);
            expect($data[0]['longitude'])->toEqual(0);
        });

        it('throws on unwritable output path', function () {
            copy(__DIR__.'/../../stubs/postal_codes.txt', $this->getTestDataPath('postal.txt'));

            $converter = new TestablePostalCodeJsonConverter;

            set_error_handler(fn () => true);
            try {
                $converter->testProcessFile(
                    $this->getTestDataPath('postal.txt'),
                    '/nonexistent/path/output.json'
                );
            } finally {
                restore_error_handler();
            }
        })->throws(GeonamesException::class);

        it('formats JSON with newlines', function () {
            copy(__DIR__.'/../../stubs/postal_codes.txt', $this->getTestDataPath('postal.txt'));

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('postal.txt'),
                $this->getTestDataPath('output.json')
            );

            $content = file_get_contents($this->getTestDataPath('output.json'));
            expect($content)->toStartWith("[\n");
            expect($content)->toEndWith("\n]");
        });

        it('updates progress bar when output is set', function () {
            copy(__DIR__.'/../../stubs/postal_codes.txt', $this->getTestDataPath('postal.txt'));

            $output = new BufferedOutput;
            $converter = new TestablePostalCodeJsonConverter;
            $converter->setOutput($output);
            $converter->testProcessFile(
                $this->getTestDataPath('postal.txt'),
                $this->getTestDataPath('output.json')
            );

            $display = $output->fetch();
            expect($display)->toContain('%');
        });

        it('handles UTF-8 place names', function () {
            $line = "TH\t10200\tกรุงเทพ\tBangkok\t10\t\t\t\t\t13.7235\t100.5147\t1\n";
            file_put_contents($this->getTestDataPath('utf8.txt'), $line);

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('utf8.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect($data[0]['place_name'])->toBe('กรุงเทพ');
        });

        it('processes multiple records with correct separators', function () {
            copy(__DIR__.'/../../stubs/postal_codes.txt', $this->getTestDataPath('postal.txt'));

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('postal.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect(count($data))->toBeGreaterThanOrEqual(1);

            // Verify the file is valid JSON (commas properly placed)
            expect(json_last_error())->toBe(JSON_ERROR_NONE);
        });

        it('handles single record', function () {
            $line = "TH\t10200\tBang Rak\tBangkok\t10\t\t\t\t\t13.7235\t100.5147\t1\n";
            file_put_contents($this->getTestDataPath('single.txt'), $line);

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('single.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect($data)->toHaveCount(1);
            expect($data[0]['postal_code'])->toBe('10200');
            expect($data[0]['place_name'])->toBe('Bang Rak');
        });

        it('handles records with all admin fields', function () {
            $line = "US\t10001\tNew York\tNew York\tNY\tNew York\t061\tManhattan\t36061\t40.7128\t-74.006\t4\n";
            file_put_contents($this->getTestDataPath('full_admin.txt'), $line);

            $converter = new TestablePostalCodeJsonConverter;
            $converter->testProcessFile(
                $this->getTestDataPath('full_admin.txt'),
                $this->getTestDataPath('output.json')
            );

            $data = json_decode(file_get_contents($this->getTestDataPath('output.json')), true);
            expect($data[0]['admin_name1'])->toBe('New York');
            expect($data[0]['admin_code1'])->toBe('NY');
        });
    });
});
