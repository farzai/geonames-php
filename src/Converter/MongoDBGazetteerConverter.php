<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use MongoDB\Client;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class MongoDBGazetteerConverter
{
    private ?OutputInterface $output = null;
    private string $connectionString;
    private string $database;
    private string $collection;
    private array $admin1Codes = [];
    private array $admin2Codes = [];

    public function __construct(string $connectionString = 'mongodb://localhost:27017', string $database = 'geonames', string $collection = 'gazetteer')
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
    public function convert(string $zipFile, string $outputFile, string $adminCodesDir): void
    {
        if (!class_exists('MongoDB\Client')) {
            throw new \RuntimeException(
                'MongoDB library not found. Please install it using: composer require mongodb/mongodb'
            );
        }

        // Load admin codes first
        $this->loadAdminCodes($adminCodesDir);

        $zip = new ZipArchive;

        if ($zip->open($zipFile) !== true) {
            throw new \RuntimeException('Failed to open ZIP file: ' . $zipFile);
        }

        // Extract to temporary directory
        $tempDir = sys_get_temp_dir() . '/geonames_' . uniqid();
        mkdir($tempDir);
        $zip->extractTo($tempDir);
        $zip->close();

        // Find the txt file
        $files = glob($tempDir . '/*.txt');
        if (empty($files)) {
            throw new \RuntimeException('No .txt file found in ZIP archive');
        }
        $txtFile = $files[0];

        // Import to MongoDB
        $this->importToMongoDB($txtFile);

        // Cleanup all files in temp directory
        $files = new \DirectoryIterator($tempDir);
        foreach ($files as $file) {
            if (!$file->isDot()) {
                unlink($file->getPathname());
            }
        }
        rmdir($tempDir);
    }

    /**
     * Load admin codes from files
     */
    private function loadAdminCodes(string $adminCodesDir): void
    {
        if ($this->output) {
            $this->output->writeln('<info>Loading administrative codes...</info>');
        }

        // Load admin1 codes
        $admin1File = $adminCodesDir . '/admin1CodesASCII.txt';
        if (file_exists($admin1File)) {
            $handle = fopen($admin1File, 'r');
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", trim($line));
                if (count($parts) >= 2) {
                    $code = $parts[0];
                    $name = $parts[1];
                    $this->admin1Codes[$code] = $name;
                }
            }
            fclose($handle);
        }

        // Load admin2 codes
        $admin2File = $adminCodesDir . '/admin2Codes.txt';
        if (file_exists($admin2File)) {
            $handle = fopen($admin2File, 'r');
            while (($line = fgets($handle)) !== false) {
                $parts = explode("\t", trim($line));
                if (count($parts) >= 2) {
                    $code = $parts[0];
                    $name = $parts[1];
                    $this->admin2Codes[$code] = $name;
                }
            }
            fclose($handle);
        }
    }

    /**
     * Import data to MongoDB
     */
    private function importToMongoDB(string $txtFile): void
    {
        // Get total lines for progress bar
        $totalLines = 0;
        $handle = fopen($txtFile, 'r');
        while (!feof($handle)) {
            $totalLines += substr_count(fread($handle, 8192), "\n");
        }
        fclose($handle);

        $progressBar = null;
        if ($this->output) {
            $progressBar = new ProgressBar($this->output, $totalLines);
            $progressBar->start();
        }

        // Initialize MongoDB client and collection
        $client = new Client($this->connectionString);
        $collection = $client->selectDatabase($this->database)->selectCollection($this->collection);
        
        // Create an index for better query performance
        $collection->createIndex(['location' => '2dsphere']);
        
        // Process file line by line
        $batchSize = 1000;
        $documents = [];
        $processedLines = 0;
        
        $handle = fopen($txtFile, 'r');
        while (($line = fgets($handle)) !== false) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 19) {
                $document = [
                    'geonameid' => (int) $parts[0],
                    'name' => $parts[1],
                    'asciiname' => $parts[2],
                    'alternatenames' => !empty($parts[3]) ? explode(',', $parts[3]) : [],
                    'location' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $parts[5], // longitude
                            (float) $parts[4], // latitude
                        ],
                    ],
                    'feature_class' => $parts[6],
                    'feature_code' => $parts[7],
                    'country_code' => $parts[8],
                    'cc2' => !empty($parts[9]) ? explode(',', $parts[9]) : [],
                    'admin1_code' => $parts[10],
                    'admin2_code' => $parts[11],
                    'admin3_code' => $parts[12],
                    'admin4_code' => $parts[13],
                    'population' => !empty($parts[14]) ? (int) $parts[14] : 0,
                    'elevation' => !empty($parts[15]) ? (int) $parts[15] : null,
                    'dem' => !empty($parts[16]) ? (int) $parts[16] : null,
                    'timezone' => $parts[17],
                    'modification_date' => $parts[18],
                ];

                // Add admin names if available
                if (!empty($parts[8]) && !empty($parts[10])) {
                    $admin1Name = $this->getAdmin1Name($parts[8], $parts[10]);
                    if ($admin1Name) {
                        $document['admin1_name'] = $admin1Name;
                    }
                }

                if (!empty($parts[8]) && !empty($parts[10]) && !empty($parts[11])) {
                    $admin2Name = $this->getAdmin2Name($parts[8], $parts[10], $parts[11]);
                    if ($admin2Name) {
                        $document['admin2_name'] = $admin2Name;
                    }
                }

                $documents[] = $document;
                
                // Insert in batches to improve performance
                if (count($documents) >= $batchSize) {
                    $collection->insertMany($documents);
                    $documents = [];
                }
            }
            
            $processedLines++;
            if ($progressBar) {
                $progressBar->setProgress($processedLines);
            }
        }
        
        // Insert remaining documents
        if (!empty($documents)) {
            $collection->insertMany($documents);
        }
        
        fclose($handle);

        if ($progressBar) {
            $progressBar->finish();
            $this->output->writeln('');
        }
    }

    /**
     * Get admin1 name from codes
     */
    private function getAdmin1Name(string $countryCode, string $admin1Code): string
    {
        return $this->admin1Codes[$countryCode . '.' . $admin1Code] ?? '';
    }

    /**
     * Get admin2 name from codes
     */
    private function getAdmin2Name(string $countryCode, string $admin1Code, string $admin2Code): string
    {
        return $this->admin2Codes[$countryCode . '.' . $admin1Code . '.' . $admin2Code] ?? '';
    }
}