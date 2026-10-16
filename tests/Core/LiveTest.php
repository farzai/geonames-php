<?php

declare(strict_types=1);

use Farzai\Geonames\Console\Cli;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotStore;

/*
 * Against the real CDN (https://data.farzai.com/geonames/v1). Skipped unless
 * GEONAMES_LIVE=1, so the normal run never touches the network:
 *   GEONAMES_LIVE=1 vendor/bin/pest --group=live
 */
it('fetches Thailand from the live snapshot, verifies it and reads it', function () {
    $store = new SnapshotStore($this->temporaryDirectory());
    $out = fopen('php://memory', 'w+');

    $code = (new Cli)->run(['fetch', 'TH', '--datasets=admin1,admin2,cities', '--sqlite'], $out, $out, $store);
    rewind($out);
    expect($code)->toBe(0, (string) stream_get_contents($out));

    $geonames = GeonamesRepository::default($store);
    $manifest = $store->manifest(Pin::bundled()->snapshot);

    expect($geonames->cities('TH')->count())->toBeGreaterThan(1000)
        ->and($geonames->admin1('TH')->count())->toBe(77)
        ->and($geonames->country('TH')?->nameTh)->toBe('ไทย')
        ->and($geonames->nearest(13.7563, 100.5018, 'TH', 1)->first()?->name())->not->toBeNull()
        ->and($geonames->search('Chiang Mai', 'TH')->first()?->countryCode())->toBe('TH')
        ->and($manifest?->snapshot)->toBe(Pin::bundled()->snapshot);
})->group('live')->skip(fn () => getenv('GEONAMES_LIVE') !== '1', 'set GEONAMES_LIVE=1 to run against data.farzai.com');
