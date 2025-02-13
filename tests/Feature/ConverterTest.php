<?php

use Farzai\Geonames\Converter\GazetteerConverter;
use Farzai\Geonames\Converter\PostalCodeConverter;
use Symfony\Component\Console\Output\ConsoleOutput;

beforeEach(function () {
    // Create test data if it doesn't exist
    if (! file_exists(__DIR__.'/../stubs/TH.zip')) {
        require __DIR__.'/../stubs/create_test_data.php';
    }
});

test('postal codes converter can convert zip to json', function () {
    // Copy test data
    copy(__DIR__.'/../stubs/TH.zip', $this->getTestDataPath('TH.zip'));

    $converter = new PostalCodeConverter;
    $converter->convert(
        $this->getTestDataPath('TH.zip'),
        $this->getTestDataPath('TH.json')
    );

    expect(file_exists($this->getTestDataPath('TH.json')))->toBeTrue();

    $data = json_decode(file_get_contents($this->getTestDataPath('TH.json')), true);
    expect($data)->toBeArray()
        ->and($data[0])->toHaveKeys([
            'country_code',
            'postal_code',
            'place_name',
            'admin_name1',
            'admin_code1',
            'admin_name2',
            'admin_code2',
            'admin_name3',
            'admin_code3',
            'latitude',
            'longitude',
            'accuracy',
        ]);
});

test('gazetteer converter can convert zip to json with admin codes', function () {
    // Copy test data
    copy(__DIR__.'/../stubs/TH_gaz.zip', $this->getTestDataPath('TH.zip'));
    copy(__DIR__.'/../stubs/admin1CodesASCII.txt', $this->getTestDataPath('admin1CodesASCII.txt'));
    copy(__DIR__.'/../stubs/admin2Codes.txt', $this->getTestDataPath('admin2Codes.txt'));

    // Debug: Check if files exist and show their contents
    $output = new ConsoleOutput;
    $output->writeln('<info>Test files:</info>');
    foreach (['TH.zip', 'admin1CodesASCII.txt', 'admin2Codes.txt'] as $file) {
        $path = $this->getTestDataPath($file);
        $output->writeln(sprintf('<info>%s exists: %s</info>', $file, file_exists($path) ? 'yes' : 'no'));
        if (file_exists($path) && ! str_ends_with($file, '.zip')) {
            $output->writeln('<info>Content:</info>');
            $output->writeln(file_get_contents($path));
        }
    }

    $converter = new GazetteerConverter;
    $converter->setOutput($output);
    $converter->convert(
        $this->getTestDataPath('TH.zip'),
        $this->getTestDataPath('TH.json'),
        $this->getTestDataPath()
    );

    expect(file_exists($this->getTestDataPath('TH.json')))->toBeTrue();

    $data = json_decode(file_get_contents($this->getTestDataPath('TH.json')), true);
    $output->writeln('<info>JSON output:</info>');
    $output->writeln(json_encode($data, JSON_PRETTY_PRINT));

    expect($data)->toBeArray()
        ->and($data[0])->toHaveKeys([
            'geoname_id',
            'name',
            'ascii_name',
            'alternate_names',
            'latitude',
            'longitude',
            'feature_class',
            'feature_code',
            'country_code',
            'cc2',
            'admin1_code',
            'admin1_name',
            'admin2_code',
            'admin2_name',
            'admin3_code',
            'admin4_code',
            'population',
            'elevation',
            'dem',
            'timezone',
            'modification_date',
        ]);
});
