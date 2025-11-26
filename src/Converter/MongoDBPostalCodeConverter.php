<?php

declare(strict_types=1);

namespace Farzai\Geonames\Converter;

use Farzai\Geonames\Exceptions\GeonamesException;
use MongoDB\Client;

/**
 * Converts GeoNames postal code data from ZIP files directly to MongoDB.
 *
 * This converter extracts postal code data from GeoNames ZIP archives
 * and imports it directly into a MongoDB collection with geospatial indexing.
 */
class MongoDBPostalCodeConverter extends AbstractConverter
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
     * Create a new MongoDB postal code converter.
     *
     * @param  string  $connectionString  MongoDB connection URI (default: mongodb://localhost:27017)
     * @param  string  $database  Target database name (default: geonames)
     * @param  string  $collection  Target collection name (default: postal_codes)
     */
    public function __construct(
        string $connectionString = 'mongodb://localhost:27017',
        string $database = 'geonames',
        string $collection = 'postal_codes'
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
     * Process the postal code data file and import to MongoDB.
     *
     * @param  string  $txtFile  Path to the source TXT file containing postal code data
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

        $this->createIndexes($collection);

        $batch = [];
        $count = 0;

        try {
            foreach ($this->streamPostalCodeRecords($txtFile) as $record) {
                $record = $this->addGeoLocation($record);
                $batch[] = $record;
                $count++;

                if ($count % self::BATCH_SIZE === 0) {
                    $collection->insertMany($batch, ['ordered' => false]);
                    $batch = [];
                }

                $progressBar?->advance();
            }

            if (! empty($batch)) {
                $collection->insertMany($batch, ['ordered' => false]);
            }
        } finally {
            $this->finishProgressBar($progressBar);
        }

        $this->output?->writeln(sprintf(
            '<info>Imported %d records to MongoDB: %s.%s</info>',
            $count,
            $this->database,
            $this->collection
        ));
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
     * Create indexes on the MongoDB collection for optimal query performance.
     *
     * @param  \MongoDB\Collection  $collection  The MongoDB collection
     */
    private function createIndexes(\MongoDB\Collection $collection): void
    {
        $collection->createIndex(['country_code' => 1]);
        $collection->createIndex(['postal_code' => 1]);
        $collection->createIndex(['country_code' => 1, 'postal_code' => 1], ['unique' => true]);
        $collection->createIndex(['location' => '2dsphere']);
    }

    /**
     * Add GeoJSON location field to a record for geospatial queries.
     *
     * @param  array<string, mixed>  $record  The postal code record
     * @return array<string, mixed> The record with added location field
     */
    private function addGeoLocation(array $record): array
    {
        if (isset($record['latitude'], $record['longitude'])) {
            $record['location'] = [
                'type' => 'Point',
                'coordinates' => [$record['longitude'], $record['latitude']],
            ];
        }

        return $record;
    }
}
