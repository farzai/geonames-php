<?php

declare(strict_types=1);

namespace Farzai\Geonames\Tests\Helpers;

/**
 * Mock MongoDB Client for testing without actual MongoDB connection.
 */
class MockMongoDBClient
{
    /**
     * @var array<array<string, mixed>>
     */
    public array $insertedDocuments = [];

    /**
     * @var array<array{keys: array<string, mixed>, options: array<string, mixed>}>
     */
    public array $createdIndexes = [];

    /**
     * @var array<string, MockMongoDBDatabase>
     */
    private array $databases = [];

    public function selectDatabase(string $name): MockMongoDBDatabase
    {
        if (! isset($this->databases[$name])) {
            $this->databases[$name] = new MockMongoDBDatabase($name, $this);
        }

        return $this->databases[$name];
    }
}

/**
 * Mock MongoDB Database.
 */
class MockMongoDBDatabase
{
    private string $name;

    private MockMongoDBClient $client;

    /**
     * @var array<string, MockMongoDBCollection>
     */
    private array $collections = [];

    public function __construct(string $name, MockMongoDBClient $client)
    {
        $this->name = $name;
        $this->client = $client;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function selectCollection(string $name): MockMongoDBCollection
    {
        if (! isset($this->collections[$name])) {
            $this->collections[$name] = new MockMongoDBCollection($name, $this->client);
        }

        return $this->collections[$name];
    }
}

/**
 * Mock MongoDB Collection.
 */
class MockMongoDBCollection
{
    private string $name;

    private MockMongoDBClient $client;

    public function __construct(string $name, MockMongoDBClient $client)
    {
        $this->name = $name;
        $this->client = $client;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param  array<string, mixed>  $keys
     * @param  array<string, mixed>  $options
     */
    public function createIndex(array $keys, array $options = []): string
    {
        $this->client->createdIndexes[] = ['keys' => $keys, 'options' => $options];

        return 'index_name';
    }

    /**
     * @param  array<array<string, mixed>>  $documents
     * @param  array<string, mixed>  $options
     */
    public function insertMany(array $documents, array $options = []): void
    {
        foreach ($documents as $doc) {
            $this->client->insertedDocuments[] = $doc;
        }
    }

    /**
     * @param  array<string, mixed>  $document
     * @param  array<string, mixed>  $options
     */
    public function insertOne(array $document, array $options = []): void
    {
        $this->client->insertedDocuments[] = $document;
    }
}
