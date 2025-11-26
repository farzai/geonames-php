<?php

use Farzai\Geonames\Converter\MongoDBGazetteerConverter;
use Farzai\Geonames\Converter\MongoDBPostalCodeConverter;
use Farzai\Geonames\Exceptions\GeonamesException;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

describe('MongoDBPostalCodeConverter', function () {
    describe('constructor', function () {
        it('accepts default parameters', function () {
            $converter = new MongoDBPostalCodeConverter;

            expect($converter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
        });

        it('accepts custom connection string in constructor', function () {
            $converter = new MongoDBPostalCodeConverter('mongodb://custom:27017');

            expect($converter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
        });

        it('accepts custom database in constructor', function () {
            $converter = new MongoDBPostalCodeConverter('mongodb://localhost:27017', 'custom_db');

            expect($converter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
        });

        it('accepts custom collection in constructor', function () {
            $converter = new MongoDBPostalCodeConverter(
                'mongodb://localhost:27017',
                'geonames',
                'custom_collection'
            );

            expect($converter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
        });
    });

    describe('fluent methods', function () {
        it('setConnectionString returns self', function () {
            $converter = new MongoDBPostalCodeConverter;

            $result = $converter->setConnectionString('mongodb://custom:27017');

            expect($result)->toBe($converter);
        });

        it('setDatabase returns self', function () {
            $converter = new MongoDBPostalCodeConverter;

            $result = $converter->setDatabase('test_db');

            expect($result)->toBe($converter);
        });

        it('setCollection returns self', function () {
            $converter = new MongoDBPostalCodeConverter;

            $result = $converter->setCollection('test_collection');

            expect($result)->toBe($converter);
        });

        it('supports method chaining', function () {
            $converter = new MongoDBPostalCodeConverter;

            $result = $converter
                ->setConnectionString('mongodb://custom:27017')
                ->setDatabase('test_db')
                ->setCollection('test_collection')
                ->setOutput(new NullOutput);

            expect($result)->toBe($converter);
        });

        it('setConnectionString allows empty string', function () {
            $converter = new MongoDBPostalCodeConverter;

            $result = $converter->setConnectionString('');

            expect($result)->toBe($converter);
        });
    });
});

describe('MongoDBGazetteerConverter', function () {
    describe('constructor', function () {
        it('accepts default parameters for gazetteer', function () {
            $converter = new MongoDBGazetteerConverter;

            expect($converter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
        });

        it('accepts custom connection string in gazetteer constructor', function () {
            $converter = new MongoDBGazetteerConverter('mongodb://custom:27017');

            expect($converter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
        });

        it('accepts custom database in gazetteer constructor', function () {
            $converter = new MongoDBGazetteerConverter('mongodb://localhost:27017', 'custom_db');

            expect($converter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
        });

        it('accepts custom collection in gazetteer constructor', function () {
            $converter = new MongoDBGazetteerConverter(
                'mongodb://localhost:27017',
                'geonames',
                'custom_gazetteer'
            );

            expect($converter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
        });
    });

    describe('fluent methods', function () {
        it('gazetteer setConnectionString returns self', function () {
            $converter = new MongoDBGazetteerConverter;

            $result = $converter->setConnectionString('mongodb://custom:27017');

            expect($result)->toBe($converter);
        });

        it('gazetteer setDatabase returns self', function () {
            $converter = new MongoDBGazetteerConverter;

            $result = $converter->setDatabase('test_db');

            expect($result)->toBe($converter);
        });

        it('gazetteer setCollection returns self', function () {
            $converter = new MongoDBGazetteerConverter;

            $result = $converter->setCollection('test_collection');

            expect($result)->toBe($converter);
        });

        it('gazetteer supports method chaining', function () {
            $converter = new MongoDBGazetteerConverter;

            $result = $converter
                ->setConnectionString('mongodb://custom:27017')
                ->setDatabase('test_db')
                ->setCollection('test_collection')
                ->setOutput(new NullOutput);

            expect($result)->toBe($converter);
        });
    });

    describe('output', function () {
        it('gazetteer accepts BufferedOutput', function () {
            $converter = new MongoDBGazetteerConverter;
            $output = new BufferedOutput;

            $result = $converter->setOutput($output);

            expect($result)->toBe($converter);
        });

        it('gazetteer accepts NullOutput', function () {
            $converter = new MongoDBGazetteerConverter;
            $output = new NullOutput;

            $result = $converter->setOutput($output);

            expect($result)->toBe($converter);
        });
    });
});

describe('MongoDB converter exception handling', function () {
    it('dependencyMissing exception contains correct message', function () {
        $exception = GeonamesException::dependencyMissing(
            'MongoDB library',
            'composer require mongodb/mongodb'
        );

        expect($exception->getMessage())
            ->toContain('MongoDB library')
            ->toContain('composer require');
    });
});

describe('MongoDB converter defaults', function () {
    it('postal converter uses postal_codes as default collection', function () {
        $converter = new MongoDBPostalCodeConverter(
            'mongodb://localhost:27017',
            'geonames'
        );

        expect($converter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
    });

    it('gazetteer converter uses gazetteer as default collection', function () {
        $converter = new MongoDBGazetteerConverter(
            'mongodb://localhost:27017',
            'geonames'
        );

        expect($converter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
    });

    it('both converters use geonames as default database', function () {
        $postalConverter = new MongoDBPostalCodeConverter;
        $gazetteerConverter = new MongoDBGazetteerConverter;

        expect($postalConverter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
        expect($gazetteerConverter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
    });

    it('both converters use localhost as default connection', function () {
        $postalConverter = new MongoDBPostalCodeConverter;
        $gazetteerConverter = new MongoDBGazetteerConverter;

        expect($postalConverter)->toBeInstanceOf(MongoDBPostalCodeConverter::class);
        expect($gazetteerConverter)->toBeInstanceOf(MongoDBGazetteerConverter::class);
    });
});
