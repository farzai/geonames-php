<?php

use Farzai\Geonames\Converter\MongoDBGazetteerConverter;
use Farzai\Geonames\Converter\MongoDBPostalCodeConverter;
use Symfony\Component\Console\Output\NullOutput;

describe('MongoDBPostalCodeConverter', function () {
    it('accepts default constructor parameters', function () {
        $converter = new MongoDBPostalCodeConverter;

        expect($converter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
    });

    it('accepts custom constructor parameters', function () {
        $converter = new MongoDBPostalCodeConverter(
            'mongodb://custom:27017',
            'custom_db',
            'custom_collection'
        );

        expect($converter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
    });

    it('setConnectionString returns self for postal codes', function () {
        $converter = new MongoDBPostalCodeConverter;

        $result = $converter->setConnectionString('mongodb://localhost:27017');

        expect($result)->toBe($converter);
    });

    it('setDatabase returns self for postal codes', function () {
        $converter = new MongoDBPostalCodeConverter;

        $result = $converter->setDatabase('test_db');

        expect($result)->toBe($converter);
    });

    it('setCollection returns self for postal codes', function () {
        $converter = new MongoDBPostalCodeConverter;

        $result = $converter->setCollection('test_collection');

        expect($result)->toBe($converter);
    });

    it('setOutput returns self for postal codes', function () {
        $converter = new MongoDBPostalCodeConverter;

        $result = $converter->setOutput(new NullOutput);

        expect($result)->toBe($converter);
    });

    it('supports method chaining for postal codes', function () {
        $converter = new MongoDBPostalCodeConverter;

        $result = $converter
            ->setConnectionString('mongodb://localhost:27017')
            ->setDatabase('test_db')
            ->setCollection('test_collection')
            ->setOutput(new NullOutput);

        expect($result)->toBe($converter);
    });
});

describe('MongoDBGazetteerConverter', function () {
    it('accepts default constructor parameters for gazetteer', function () {
        $converter = new MongoDBGazetteerConverter;

        expect($converter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
    });

    it('accepts custom constructor parameters for gazetteer', function () {
        $converter = new MongoDBGazetteerConverter(
            'mongodb://custom:27017',
            'custom_db',
            'custom_collection'
        );

        expect($converter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
    });

    it('setConnectionString returns self for gazetteer', function () {
        $converter = new MongoDBGazetteerConverter;

        $result = $converter->setConnectionString('mongodb://localhost:27017');

        expect($result)->toBe($converter);
    });

    it('setDatabase returns self for gazetteer', function () {
        $converter = new MongoDBGazetteerConverter;

        $result = $converter->setDatabase('test_db');

        expect($result)->toBe($converter);
    });

    it('setCollection returns self for gazetteer', function () {
        $converter = new MongoDBGazetteerConverter;

        $result = $converter->setCollection('test_collection');

        expect($result)->toBe($converter);
    });

    it('setOutput returns self for gazetteer', function () {
        $converter = new MongoDBGazetteerConverter;

        $result = $converter->setOutput(new NullOutput);

        expect($result)->toBe($converter);
    });

    it('supports method chaining for gazetteer', function () {
        $converter = new MongoDBGazetteerConverter;

        $result = $converter
            ->setConnectionString('mongodb://localhost:27017')
            ->setDatabase('test_db')
            ->setCollection('test_collection')
            ->setOutput(new NullOutput);

        expect($result)->toBe($converter);
    });
});
