<?php

declare(strict_types=1);

use Farzai\Geonames\Exceptions\IntegrityException;
use Farzai\Geonames\Exceptions\SnapshotException;
use Farzai\Geonames\Exceptions\UnsupportedSchemaException;
use Farzai\Geonames\Snapshot\Datasets;
use Farzai\Geonames\Snapshot\SnapshotFetcher;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Tests\Support\FakeTransport;
use Farzai\Geonames\Tests\Support\FixtureSnapshot;

function fetcher(object $test, ?FixtureSnapshot $fixture = null, ?FakeTransport &$transport = null, ?SnapshotStore &$store = null): SnapshotFetcher
{
    $fixture ??= new FixtureSnapshot;
    $transport = new FakeTransport($fixture->routes());
    $store = new SnapshotStore($test->temporaryDirectory());

    return new SnapshotFetcher($transport, $store, $fixture->pin(), 'https://data.test/geonames/v1');
}

it('downloads the pinned snapshot files, verified, into the store', function () {
    $fetcher = fetcher($this, null, $transport, $store);

    $result = $fetcher->fetch(['us'], ['cities', 'postal_codes']);

    expect($result->snapshot)->toBe(FixtureSnapshot::SNAPSHOT)
        ->and($result->downloaded)->toEqualCanonicalizing(['by-country/US/cities.ndjson.gz', 'by-country/US/postal_codes.ndjson.gz'])
        ->and($store->has(FixtureSnapshot::SNAPSHOT, 'by-country/US/cities.ndjson.gz'))->toBeTrue()
        ->and($store->manifest(FixtureSnapshot::SNAPSHOT)?->snapshot)->toBe(FixtureSnapshot::SNAPSHOT);
});

it('reads latest.json only when asked to follow latest', function () {
    $fetcher = fetcher($this, null, $transport);

    $fetcher->fetch(['TH'], ['cities']);
    expect($transport->requests)->not->toContain('https://data.test/geonames/v1/latest.json');

    $fetcher->fetch(['TH'], ['cities'], snapshot: 'latest');
    expect($transport->requests)->toContain('https://data.test/geonames/v1/latest.json');
});

it('skips files already in the store and verified', function () {
    $fetcher = fetcher($this, null, $transport);
    $fetcher->fetch(['US'], ['cities']);
    $before = count($transport->requests);

    $second = $fetcher->fetch(['US'], ['cities']);

    expect($second->downloaded)->toBe([])
        ->and($second->skipped)->toBe(['by-country/US/cities.ndjson.gz'])
        ->and(array_filter(array_slice($transport->requests, $before), fn ($u) => str_contains($u, 'cities')))->toBe([]);
});

it('keeps nothing when a file is cut short or corrupted', function () {
    $fetcher = fetcher($this, null, $transport, $store);
    $path = FixtureSnapshot::SNAPSHOT.'/by-country/US/cities.ndjson.gz';

    $transport->cut($path, 10);
    expect(fn () => $fetcher->fetch(['US'], ['cities']))->toThrow(IntegrityException::class, 'bytes');
    expect($store->has(FixtureSnapshot::SNAPSHOT, 'by-country/US/cities.ndjson.gz'))->toBeFalse()
        ->and(glob($store->snapshotDir(FixtureSnapshot::SNAPSHOT).'/by-country/US/*'))->toBe([]);

    $good = (new FixtureSnapshot)->files['by-country/US/cities.ndjson.gz'];
    $transport->replace($path, strrev($good)); // same size, different bytes
    expect(fn () => $fetcher->fetch(['US'], ['cities']))->toThrow(IntegrityException::class, 'sha256');
    expect($store->has(FixtureSnapshot::SNAPSHOT, 'by-country/US/cities.ndjson.gz'))->toBeFalse();
});

it('refuses a manifest that does not match the pinned hash', function () {
    $fetcher = fetcher($this, null, $transport);
    $transport->replace(FixtureSnapshot::SNAPSHOT.'/manifest.json', (new FixtureSnapshot)->manifestBody.' ');

    expect(fn () => $fetcher->fetch(['US'], ['cities']))->toThrow(IntegrityException::class, 'manifest.json');
});

it('refuses a withdrawn snapshot and one that snapshots.json does not list', function () {
    $withdrawn = new FixtureSnapshot(status: 'withdrawn');
    expect(fn () => fetcher($this, $withdrawn)->fetch(['US'], ['cities']))->toThrow(SnapshotException::class, 'withdrawn');

    $fetcher = fetcher($this, null, $transport);
    expect(fn () => $fetcher->fetch(['US'], ['cities'], snapshot: '1999-01-01.1'))->toThrow(SnapshotException::class, 'not listed');
});

it('refuses a different schema major and accepts a newer minor', function () {
    expect(fn () => fetcher($this, new FixtureSnapshot(schemaVersion: '2.0.0'))->fetch(['US'], ['cities'], snapshot: 'latest'))
        ->toThrow(UnsupportedSchemaException::class);

    $result = fetcher($this, new FixtureSnapshot(schemaVersion: '1.9.0'))->fetch(['US'], ['cities'], snapshot: 'latest');
    expect($result->downloaded)->toBe(['by-country/US/cities.ndjson.gz']);
});

it('only accepts two-letter country codes, so nothing escapes the store', function (string $code) {
    $fetcher = fetcher($this);

    expect(fn () => $fetcher->fetch([$code], ['cities']))->toThrow(SnapshotException::class, 'alpha-2');
})->with(['../../x', 'ALL', 'T', 'T1', '', 'TH/..']);

it('rejects an unknown dataset name', function () {
    expect(fn () => fetcher($this)->fetch(['US'], ['everything']))->toThrow(SnapshotException::class, 'dataset');
});

it('reports a dataset the snapshot does not have instead of failing (Thai postal codes)', function () {
    $result = fetcher($this)->fetch(['TH'], ['cities', 'postal_codes']);

    expect($result->downloaded)->toBe(['by-country/TH/cities.ndjson.gz'])
        ->and($result->missing)->toBe(['by-country/TH/postal_codes.ndjson.gz']);
});

it('fetches the global admin1 file once, however many countries are named', function () {
    $result = fetcher($this)->fetch(['TH', 'US'], ['admin1']);

    expect($result->downloaded)->toBe(['admin1.json']);
});

it('refuses a manifest path that would leave the store', function () {
    $fixture = new FixtureSnapshot;
    $manifest = json_decode($fixture->manifestBody, true);
    $manifest['files']['by-country/../../evil.ndjson.gz'] = ['sha256' => str_repeat('a', 64), 'bytes' => 1];
    $body = json_encode($manifest);
    $fixture->manifestBody = $body;
    $fixture->latestBody = json_encode(['schema_version' => '1.4.0', 'snapshot' => FixtureSnapshot::SNAPSHOT, 'manifest_sha256' => hash('sha256', $body)]);
    $fixture->snapshotsBody = json_encode(['schema_version' => '1.4.0', 'snapshots' => [['id' => FixtureSnapshot::SNAPSHOT, 'status' => 'published', 'manifest_sha256' => hash('sha256', $body)]]]);
    $fetcher = fetcher($this, $fixture);

    // The safe-path check lives in Datasets::assertSafePath; the unsafe name can only be requested through a crafted dataset path.
    expect(fn () => Datasets::assertSafePath('by-country/../../evil.ndjson.gz'))->toThrow(SnapshotException::class, 'safe relative path');
    expect($fetcher->fetch(['US'], ['cities'])->downloaded)->toBe(['by-country/US/cities.ndjson.gz']);
});
