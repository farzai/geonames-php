<?php

declare(strict_types=1);

use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Models\Kind;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotStore;

$data = dirname(__DIR__, 2).'/data';

it('bundles exactly the files the pinned manifest names, byte for byte', function () use ($data) {
    $pin = json_decode((string) file_get_contents($data.'/snapshot.json'), true);

    expect($pin['files'])->toHaveKeys(['countries.json', 'kinds.json', 'time_zones.json', 'schema.json'])
        ->and($pin['manifest_sha256'])->toMatch('/^[0-9a-f]{64}$/')
        ->and(Pin::bundled()->snapshot)->toBe($pin['snapshot']);

    foreach ($pin['files'] as $file => $sha256) {
        expect(hash_file('sha256', $data.'/'.$file))->toBe($sha256, "$file differs from the pinned manifest");
    }
});

it('states the GeoNames attribution and the credits that go with the data', function () use ($data) {
    $pin = json_decode((string) file_get_contents($data.'/snapshot.json'), true);

    expect($pin['attribution'])->toBe('Data from GeoNames (geonames.org), licensed under CC BY 4.0')
        ->and($pin['credits'])->not->toBeEmpty();
});

/** Required keys of a schema $def: the contract the models rely on. */
function requiredOf(string $def): array
{
    $schema = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/data/schema.json'), true);

    return $schema['$defs'][$def]['required'];
}

it('reads the bundled countries as the schema describes them', function () {
    $geonames = GeonamesRepository::default(new SnapshotStore(sys_get_temp_dir().'/geonames-empty-'.bin2hex(random_bytes(4))));
    $countries = $geonames->countries();

    expect($countries)->toHaveCount(252)
        ->and($countries->pluck('iso2')->unique())->toHaveCount(252, 'iso2 is unique')
        ->and($countries->pluck('iso3')->unique())->toHaveCount(252, 'iso3 is unique')
        ->and($geonames->country('TH')?->nameTh)->toBe('ไทย')
        ->and($geonames->country('TH')?->currency()['code'])->toBe('THB')
        ->and($geonames->country('TH')?->timeZones())->toBe(['Asia/Bangkok']);

    foreach (requiredOf('country') as $key) {
        expect($countries->first()->toArray())->toHaveKey($key);
    }
});

it('bundles a kinds table whose kinds this package knows, and time zones that are IANA names', function () {
    $geonames = GeonamesRepository::default(new SnapshotStore(sys_get_temp_dir().'/geonames-empty-'.bin2hex(random_bytes(4))));

    foreach ($geonames->kinds() as $kind) {
        foreach (requiredOf('kind_entry') as $key) {
            expect($kind)->toHaveKey($key);
        }
        expect(Kind::KNOWN)->toContain($kind['kind']);
    }
    expect($geonames->timeZones('TH'))->toBe(['Asia/Bangkok'])
        ->and($geonames->timeZones())->each->toMatch('#^[A-Za-z_]+(/[A-Za-z_+\-0-9]+)*$#');
});
