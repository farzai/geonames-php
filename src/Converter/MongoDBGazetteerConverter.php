<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Farzai\Geonames\Exceptions\GeonamesException;
use MongoDB\Client;

/**
 * Converts GeoNames gazetteer data from ZIP files directly to MongoDB.
 *
 * This converter extracts geographical feature data from GeoNames ZIP archives
 * and imports it directly into a MongoDB collection with geospatial indexing.
 */
class MongoDBGazetteerConverter extends AbstractGazetteerConverter
{
    /**
     * MongoDB connection string.
     */
    private string $connectionString;

    /**
     * Target MongoDB database name.
     */
    private string $database;

    /**
     * Target MongoDB collection name.
     */
    private string $collection;

    /**
     * Create a new MongoDB gazetteer converter.
     *
     * @param  string  $connectionString  MongoDB connection URI (default: mongodb://localhost:27017)
     * @param  string  $database  Target database name (default: geonames)
     * @param  string  $collection  Target collection name (default: gazetteer)
     */
    public function __construct(
        string $connectionString = 'mongodb://localhost:27017',
        string $database = 'geonames',
        string $collection = 'gazetteer'
    ) {
        $this->connectionString = $connectionString;
        $this->database = $database;
        $this->collection = $collection;
    }

    /**
     * Set the MongoDB connection string.
     *
     * @param  string  $connectionString  MongoDB connection URI
     * @return static Returns self for method chaining
     */
    public function setConnectionString(string $connectionString): static
    {
        $this->connectionString = $connectionString;

        return $this;
    }

    /**
     * Set the target MongoDB database name.
     *
     * @param  string  $database  Database name
     * @return static Returns self for method chaining
     */
    public function setDatabase(string $database): static
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Set the target MongoDB collection name.
     *
     * @param  string  $collection  Collection name
     * @return static Returns self for method chaining
     */
    public function setCollection(string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Process the gazetteer data file and import to MongoDB.
     *
     * @param  string  $txtFile  Path to the source TXT file containing gazetteer data
     * @param  string  $outputFile  Unused for MongoDB output (kept for interface compatibility)
     *
     * @throws GeonamesException When processing fails or MongoDB library is not available
     */
    protected function processFile(string $txtFile, string $outputFile): void
    {
        $this->ensureMongoDBAvailable();

        $totalLines = $this->countLines($txtFile);
        $progressBar = $this->createProgressBar($totalLines);

        $client = new Client($this->connectionString);
        $collection = $client->selectDatabase($this->database)->selectCollection($this->collection);

        $collection->createIndex(['location' => '2dsphere']);

        $handle = fopen($txtFile, 'r');
        if ($handle === false) {
            throw GeonamesException::fileOperationFailed('open', $txtFile);
        }

        $documents = [];
        $processedLines = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $record = $this->parseGazetteerLine(trim($line));

                if ($record !== null) {
                    $document = $this->transformToMongoDocument($record);
                    $documents[] = $document;

                    if (count($documents) >= self::BATCH_SIZE) {
                        $collection->insertMany($documents);
                        $documents = [];
                    }
                }

                $processedLines++;
                $progressBar?->setProgress($processedLines);
            }

            if (! empty($documents)) {
                $collection->insertMany($documents);
            }
        } finally {
            fclose($handle);
            $this->finishProgressBar($progressBar);
        }
    }

    /**
     * Ensure the MongoDB PHP library is available.
     *
     * @throws GeonamesException When the MongoDB library is not installed
     */
    private function ensureMongoDBAvailable(): void
    {
        if (! class_exists(Client::class)) {
            throw GeonamesException::dependencyMissing(
                'MongoDB library',
                'composer require mongodb/mongodb'
            );
        }
    }

    /**
     * Transform a gazetteer record into a MongoDB document with geospatial data.
     *
     * @param  array<string, mixed>  $record  The parsed gazetteer record
     * @return array<string, mixed> The MongoDB document
     */
    private function transformToMongoDocument(array $record): array
    {
        return [
            'geonameid' => $record['geoname_id'],
            'name' => $record['name'],
            'asciiname' => $record['ascii_name'],
            'alternatenames' => $record['alternate_names'],
            'location' => [
                'type' => 'Point',
                'coordinates' => [
                    $record['longitude'],
                    $record['latitude'],
                ],
            ],
            'feature_class' => $record['feature_class'],
            'feature_code' => $record['feature_code'],
            'country_code' => $record['country_code'],
            'cc2' => $record['cc2'],
            'admin1_code' => $record['admin1_code'],
            'admin1_name' => $record['admin1_name'],
            'admin2_code' => $record['admin2_code'],
            'admin2_name' => $record['admin2_name'],
            'admin3_code' => $record['admin3_code'],
            'admin4_code' => $record['admin4_code'],
            'population' => $record['population'],
            'elevation' => $record['elevation'],
            'dem' => $record['dem'],
            'timezone' => $record['timezone'],
            'modification_date' => $record['modification_date'],
        ];
    }
}
