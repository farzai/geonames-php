<?php

declare(strict_types=1);

use Farzai\Geonames\Exceptions\GeonamesException;
use Farzai\Geonames\Exceptions\IntegrityException;
use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\Exceptions\NotFoundException;
use Farzai\Geonames\Exceptions\PostalCodesElsewhereException;
use Farzai\Geonames\Exceptions\SnapshotException;
use Farzai\Geonames\Exceptions\UnsupportedSchemaException;

it('has one base type for everything the package throws on purpose', function (Throwable $exception) {
    expect($exception)->toBeInstanceOf(GeonamesException::class);
})->with([
    'not found' => fn () => NotFoundException::for('country', 'ZZ'),
    'not fetched' => fn () => NotFetchedException::country('TH', ['cities'], '2026-10-16.1'),
    'integrity' => fn () => IntegrityException::checksum('x', 'a', 'b'),
    'snapshot' => fn () => SnapshotException::withdrawn('2026-10-16.1', 'withdrawn'),
    'schema' => fn () => UnsupportedSchemaException::major('2.0.0', 1),
    'postal elsewhere' => fn () => PostalCodesElsewhereException::thailand(),
]);

it('names the command that fetches the missing data', function () {
    $message = NotFetchedException::country('TH', ['cities', 'admin2'], '2026-10-16.1')->getMessage();

    expect($message)->toContain('geonames fetch TH --datasets=cities,admin2')
        ->and($message)->toContain('php artisan geonames:fetch TH --datasets=cities,admin2')
        ->and($message)->toContain('2026-10-16.1');
});

it('points Thai postal codes at farzai/thailand-address', function () {
    expect(PostalCodesElsewhereException::thailand()->getMessage())->toContain('farzai/thailand-address');
});
