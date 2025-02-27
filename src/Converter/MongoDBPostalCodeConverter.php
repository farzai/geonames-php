<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Generator;
use MongoDB\Client;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class MongoDBPostalCodeConverter
{
    private ?OutputInterface $output = null;

    private string $connectionString;

    private string $database;

    private string $collection;

    public function __construct(string $connectionString = 'mongodb://localhost:27017', string $database = 'geonames', string $collection = 'postal_codes')
    {
        $this->connectionString = $connectionString;
        $this->database = $database;
        $this->collection = $collection;
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function setConnectionString(string $connectionString): self
    {
        $this->connectionString = $connectionString;

        return $this;
    }

    public function setDatabase(string $database): self
    {
        $this->database = $database;

        return $this;
    }

    public function setCollection(string $collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Convert ZIP file to MongoDB documents
     */
    public function convert(string $zipFile, string $outputFile): void
    {
        if (! class_exists('MongoDB\Client')) {
            throw new \RuntimeException(
                'MongoDB library not found. Please install it using: composer require mongodb/mongodb'
            );
        }

        $zip = new ZipArchive;

        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('Failed to open ZIP file: '.$zipFile);
        }

        // Extract to temporary directory
        $tempDir = sys_get_temp_dir().'/geonames_'.uniqid();
        mkdir($tempDir);
        $zip->extractTo($tempDir);
        $zip->close();

        // Find the txt file
        $files = glob($tempDir.'/*.txt');
        if (empty($files)) {
            throw new \RuntimeException('No .txt file found in ZIP archive');
        }
        $txtFile = $files[0];

        // Convert to MongoDB with progress bar
        $this->importToMongoDB($txtFile);

        // Cleanup all files in temp directory
        $files = new \DirectoryIterator($tempDir);
        foreach ($files as $file) {
            if (! $file->isDot()) {
                unlink($file->getPathname());
            }
        }
        rmdir($tempDir);
    }

    /**
     * Import data to MongoDB
     */
    private function importToMongoDB(string $txtFile): void
    {
        $totalLines = $this->countLines($txtFile);

        $progressBar = null;
        if ($this->output) {
            $progressBar = new ProgressBar($this->output, $totalLines);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
            $progressBar->start();
        }

        // Connect to MongoDB
        $client = new Client($this->connectionString);
        $collection = $client->selectDatabase($this->database)->selectCollection($this->collection);

        // Create indexes for common queries
        $collection->createIndex(['country_code' => 1]);
        $collection->createIndex(['postal_code' => 1]);
        $collection->createIndex(['country_code' => 1, 'postal_code' => 1], ['unique' => true]);
        $collection->createIndex([
            'location' => '2dsphere',
        ]);

        // Process and insert records in chunks for better performance
        $batchSize = 1000;
        $batch = [];
        $count = 0;

        foreach ($this->streamRecords($txtFile) as $record) {
            // Add geospatial data for MongoDB
            if (isset($record['latitude']) && isset($record['longitude'])) {
                $record['location'] = [
                    'type' => 'Point',
                    'coordinates' => [$record['longitude'], $record['latitude']],
                ];
            }

            $batch[] = $record;
            $count++;

            if ($count % $batchSize === 0) {
                $collection->insertMany($batch, ['ordered' => false]);
                $batch = [];
            }

            if ($progressBar) {
                $progressBar->advance();
            }
        }

        // Insert any remaining records
        if (! empty($batch)) {
            $collection->insertMany($batch, ['ordered' => false]);
        }

        if ($progressBar) {
            $progressBar->finish();
            $this->output->writeln('');
            $this->output->writeln(sprintf('<info>Imported %d records to MongoDB: %s.%s</info>',
                $count, $this->database, $this->collection));
        }
    }

    /**
     * Stream records from TXT file
     */
    private function streamRecords(string $txtFile): Generator
    {
        $handle = fopen($txtFile, 'r');

        while (($line = fgets($handle)) !== false) {
            $data = str_getcsv(trim($line), "\t", '"', '\\');

            if (count($data) < 9) {
                continue;
            }

            yield [
                'country_code' => $data[0],
                'postal_code' => $data[1],
                'place_name' => $data[2],
                'admin_name1' => $data[3],
                'admin_code1' => $data[4],
                'admin_name2' => $data[5] ?? '',
                'admin_code2' => $data[6] ?? '',
                'admin_name3' => $data[7] ?? '',
                'admin_code3' => $data[8] ?? '',
                'latitude' => isset($data[9]) ? (float) $data[9] : null,
                'longitude' => isset($data[10]) ? (float) $data[10] : null,
                'accuracy' => isset($data[11]) ? (int) $data[11] : null,
            ];
        }

        fclose($handle);
    }

    /**
     * Count lines in file efficiently
     */
    private function countLines(string $file): int
    {
        $handle = fopen($file, 'r');
        $lines = 0;

        while (! feof($handle)) {
            $lines += substr_count(fread($handle, 8192), "\n");
        }

        fclose($handle);

        return $lines;
    }
}
