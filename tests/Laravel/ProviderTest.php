<?php

declare(strict_types=1);

use Farzai\Geonames\Geonames;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Laravel\Facades\Geonames as GeonamesFacade;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Tests\Support\FixtureSnapshot;

it('registers the repository on the configured store and snapshot', function () {
    $this->fetch(['TH'], ['countries', 'cities']);

    expect(app(SnapshotStore::class)->root())->toBe($this->store)
        ->and(app(GeonamesRepository::class)->snapshot())->toBe(FixtureSnapshot::SNAPSHOT)
        ->and(GeonamesFacade::country('TH')?->nameTh)->toBe('ไทย')
        ->and(Geonames::cities('TH'))->toHaveCount(2);
});

it('keeps the static class and the container in step when a fake is swapped in', function () {
    $fake = GeonamesRepository::fromArrays(countries: [['iso2' => 'XX', 'iso3' => 'XXX', 'iso_numeric' => '999', 'name' => 'Testland', 'names' => []]]);

    Geonames::swap($fake);
    try {
        expect(app(GeonamesRepository::class))->toBe($fake)
            ->and(GeonamesFacade::country('XX')?->name())->toBe('Testland');
    } finally {
        Geonames::swap(null);
    }
    expect(app(GeonamesRepository::class))->not->toBe($fake);
});

it('merges the default config', function () {
    expect(config('geonames.tables.cities'))->toBe('geonames_cities')
        ->and(config('geonames.base_url'))->toBe('https://data.test/geonames/v1');
});
