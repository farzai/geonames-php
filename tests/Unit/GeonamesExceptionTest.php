<?php

use Farzai\Geonames\Exceptions\GeonamesException;

describe('GeonamesException', function () {
    describe('fileOperationFailed', function () {
        it('creates exception with operation and path', function () {
            $exception = GeonamesException::fileOperationFailed('open', '/path/to/file.txt');

            expect($exception)
                ->toBeInstanceOf(GeonamesException::class)
                ->and($exception->getMessage())
                ->toBe('Failed to open file: /path/to/file.txt');
        });

        it('includes reason when provided', function () {
            $exception = GeonamesException::fileOperationFailed('write', '/path/to/file.txt', 'Permission denied');

            expect($exception->getMessage())
                ->toBe('Failed to write file: /path/to/file.txt - Permission denied');
        });

        it('handles null reason gracefully', function () {
            $exception = GeonamesException::fileOperationFailed('read', '/file.txt', null);

            expect($exception->getMessage())
                ->toBe('Failed to read file: /file.txt')
                ->not->toContain(' - ');
        });
    });

    describe('zipOperationFailed', function () {
        it('creates exception for zip file failure', function () {
            $exception = GeonamesException::zipOperationFailed('/path/to/archive.zip');

            expect($exception->getMessage())
                ->toBe('Failed to open ZIP file: /path/to/archive.zip');
        });

        it('includes reason when provided', function () {
            $exception = GeonamesException::zipOperationFailed('/archive.zip', 'Corrupted archive');

            expect($exception->getMessage())
                ->toBe('Failed to open ZIP file: /archive.zip - Corrupted archive');
        });
    });

    describe('dataNotFound', function () {
        it('creates exception with data type and location', function () {
            $exception = GeonamesException::dataNotFound('.txt file', 'ZIP archive');

            expect($exception->getMessage())
                ->toBe('No .txt file found in ZIP archive');
        });
    });

    describe('dependencyMissing', function () {
        it('creates exception with dependency and install command', function () {
            $exception = GeonamesException::dependencyMissing('MongoDB library', 'composer require mongodb/mongodb');

            expect($exception->getMessage())
                ->toBe('MongoDB library not found. Please install it using: composer require mongodb/mongodb');
        });
    });

    it('extends RuntimeException', function () {
        $exception = GeonamesException::fileOperationFailed('test', '/test');

        expect($exception)->toBeInstanceOf(\RuntimeException::class);
    });
});
