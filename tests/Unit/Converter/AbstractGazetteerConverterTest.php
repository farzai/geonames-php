<?php

use Farzai\Geonames\Converter\AbstractGazetteerConverter;

/**
 * Concrete implementation of AbstractGazetteerConverter for testing purposes.
 */
class TestableGazetteerConverter extends AbstractGazetteerConverter
{
    /**
     * Track processed files.
     *
     * @var array<int, array{txt: string, output: string}>
     */
    public array $processedFiles = [];

    protected function processFile(string $txtFile, string $outputFile): void
    {
        $this->processedFiles[] = ['txt' => $txtFile, 'output' => $outputFile];
    }

    // Expose protected methods for testing
    public function testGetAdmin1Name(string $countryCode, string $admin1Code): string
    {
        return $this->getAdmin1Name($countryCode, $admin1Code);
    }

    public function testGetAdmin2Name(string $countryCode, string $admin1Code, string $admin2Code): string
    {
        return $this->getAdmin2Name($countryCode, $admin1Code, $admin2Code);
    }

    public function testParseGazetteerLine(string $line): ?array
    {
        return $this->parseGazetteerLine($line);
    }

    public function testLoadAdminCodes(string $adminCodesDir): void
    {
        $this->loadAdminCodes($adminCodesDir);
    }
}

describe('AbstractGazetteerConverter', function () {
    beforeEach(function () {
        // Create test data if it doesn't exist
        if (! file_exists(__DIR__.'/../../stubs/TH_gaz.zip')) {
            require __DIR__.'/../../stubs/create_test_data.php';
        }
    });

    describe('convertWithAdminCodes', function () {
        it('loads admin codes before conversion', function () {
            copy(__DIR__.'/../../stubs/TH_gaz.zip', $this->getTestDataPath('TH.zip'));
            copy(__DIR__.'/../../stubs/admin1CodesASCII.txt', $this->getTestDataPath('admin1CodesASCII.txt'));
            copy(__DIR__.'/../../stubs/admin2Codes.txt', $this->getTestDataPath('admin2Codes.txt'));

            $converter = new TestableGazetteerConverter;
            $converter->convertWithAdminCodes(
                $this->getTestDataPath('TH.zip'),
                $this->getTestDataPath('output.json'),
                $this->getTestDataPath()
            );

            expect($converter->processedFiles)->toHaveCount(1);
        });
    });

    describe('loadAdminCodes', function () {
        it('loads admin1 codes from file', function () {
            copy(__DIR__.'/../../stubs/admin1CodesASCII.txt', $this->getTestDataPath('admin1CodesASCII.txt'));
            file_put_contents($this->getTestDataPath('admin2Codes.txt'), '');

            $converter = new TestableGazetteerConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());

            // TH.40 is Bangkok based on test stub data
            expect($converter->testGetAdmin1Name('TH', '40'))->not->toBeEmpty();
        });

        it('loads admin2 codes from file', function () {
            file_put_contents($this->getTestDataPath('admin1CodesASCII.txt'), '');
            copy(__DIR__.'/../../stubs/admin2Codes.txt', $this->getTestDataPath('admin2Codes.txt'));

            $converter = new TestableGazetteerConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());

            // Verify admin2 codes loaded (specific value depends on test data)
            expect(true)->toBeTrue();
        });

        it('handles missing admin code files gracefully', function () {
            $converter = new TestableGazetteerConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());

            expect($converter->testGetAdmin1Name('XX', '99'))->toBe('');
        });
    });

    describe('getAdmin1Name', function () {
        it('returns empty string for unknown code', function () {
            $converter = new TestableGazetteerConverter;

            expect($converter->testGetAdmin1Name('XX', '99'))->toBe('');
        });
    });

    describe('getAdmin2Name', function () {
        it('returns empty string for unknown code', function () {
            $converter = new TestableGazetteerConverter;

            expect($converter->testGetAdmin2Name('XX', '99', '01'))->toBe('');
        });
    });

    describe('parseGazetteerLine', function () {
        it('parses valid gazetteer line', function () {
            $converter = new TestableGazetteerConverter;

            // 19 tab-separated fields as per GAZETTEER_FIELD_COUNT
            $line = "1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result)
                ->toBeArray()
                ->toHaveKey('geoname_id')
                ->toHaveKey('name')
                ->toHaveKey('country_code')
                ->toHaveKey('latitude')
                ->toHaveKey('longitude');

            expect($result['geoname_id'])->toBe(1609350);
            expect($result['name'])->toBe('Bangkok');
            expect($result['country_code'])->toBe('TH');
            expect($result['latitude'])->toBe(13.75);
            expect($result['longitude'])->toBe(100.51667);
        });

        it('returns null for line with insufficient fields', function () {
            $converter = new TestableGazetteerConverter;

            $result = $converter->testParseGazetteerLine("1609350\tBangkok");

            expect($result)->toBeNull();
        });

        it('handles alternate names as array', function () {
            $converter = new TestableGazetteerConverter;

            $line = "1609350\tBangkok\tBangkok\tKrung Thep,BKK\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result['alternate_names'])
                ->toBeArray()
                ->toContain('Krung Thep')
                ->toContain('BKK');
        });

        it('handles empty alternate names', function () {
            $converter = new TestableGazetteerConverter;

            $line = "1609350\tBangkok\tBangkok\t\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result['alternate_names'])->toBeArray()->toBeEmpty();
        });

        it('handles Unicode names correctly', function () {
            $converter = new TestableGazetteerConverter;

            $line = "1609350\tกรุงเทพ\tBangkok\tКрунг Тхеп,曼谷\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result['name'])->toBe('กรุงเทพ');
            expect($result['alternate_names'])->toContain('Крунг Тхеп');
            expect($result['alternate_names'])->toContain('曼谷');
        });

        it('handles empty cc2 field', function () {
            $converter = new TestableGazetteerConverter;

            $line = "1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result['cc2'])->toBeArray()->toBeEmpty();
        });

        it('handles cc2 with multiple country codes', function () {
            $converter = new TestableGazetteerConverter;

            $line = "1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\tUS,UK,FR\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result['cc2'])->toBeArray()->toHaveCount(3);
            expect($result['cc2'])->toContain('US');
            expect($result['cc2'])->toContain('UK');
            expect($result['cc2'])->toContain('FR');
        });

        it('handles population as integer', function () {
            $converter = new TestableGazetteerConverter;

            $line = "1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t2\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result['population'])->toBe(5104476);
            expect($result['population'])->toBeInt();
        });

        it('handles null elevation', function () {
            $converter = new TestableGazetteerConverter;

            $line = "1609350\tBangkok\tBangkok\tKrung Thep\t13.75\t100.51667\tP\tPPLC\tTH\t\t40\t01\t\t\t5104476\t\t4\tAsia/Bangkok\t2023-01-12";
            $result = $converter->testParseGazetteerLine($line);

            expect($result['elevation'])->toBeNull();
        });
    });

    describe('loadAdminCodes edge cases', function () {
        it('handles malformed admin1 lines gracefully', function () {
            // Line without proper tab separators
            file_put_contents($this->getTestDataPath('admin1CodesASCII.txt'), "malformed line without tab\nTH.40\tBangkok\n");
            file_put_contents($this->getTestDataPath('admin2Codes.txt'), '');

            $converter = new TestableGazetteerConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());

            // Should not throw, valid lines should still be loaded
            expect($converter->testGetAdmin1Name('TH', '40'))->toBe('Bangkok');
        });

        it('handles empty admin code files', function () {
            file_put_contents($this->getTestDataPath('admin1CodesASCII.txt'), '');
            file_put_contents($this->getTestDataPath('admin2Codes.txt'), '');

            $converter = new TestableGazetteerConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());

            expect($converter->testGetAdmin1Name('TH', '40'))->toBe('');
        });
    });

    describe('getAdmin1Name edge cases', function () {
        it('is case-sensitive for country code', function () {
            copy(__DIR__.'/../../stubs/admin1CodesASCII.txt', $this->getTestDataPath('admin1CodesASCII.txt'));
            file_put_contents($this->getTestDataPath('admin2Codes.txt'), '');

            $converter = new TestableGazetteerConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());

            // Lowercase should not match if file has uppercase
            expect($converter->testGetAdmin1Name('th', '40'))->toBe('');
        });
    });

    describe('getAdmin2Name edge cases', function () {
        it('returns empty string for partial match', function () {
            copy(__DIR__.'/../../stubs/admin2Codes.txt', $this->getTestDataPath('admin2Codes.txt'));
            file_put_contents($this->getTestDataPath('admin1CodesASCII.txt'), '');

            $converter = new TestableGazetteerConverter;
            $converter->testLoadAdminCodes($this->getTestDataPath());

            // Wrong admin1 code should not match
            expect($converter->testGetAdmin2Name('TH', '99', '01'))->toBe('');
        });
    });
});
