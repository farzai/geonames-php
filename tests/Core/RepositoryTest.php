<?php

declare(strict_types=1);

use Farzai\Geonames\Data\Reader;
use Farzai\Geonames\Exceptions\GeonamesException;
use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\Exceptions\NotFoundException;
use Farzai\Geonames\Exceptions\PostalCodesElsewhereException;
use Farzai\Geonames\Geonames;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Models\City;
use Farzai\Geonames\Snapshot\SnapshotFetcher;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Source\StoreSource;
use Farzai\Geonames\Tests\Support\FakeTransport;
use Farzai\Geonames\Tests\Support\FixtureSnapshot;

/**
 * A repository over a store that holds the fixture snapshot, fetched the real way.
 *
 * @param  list<string>  $countries
 * @param  list<string>  $datasets
 */
function repositoryWith(object $test, array $countries = [], array $datasets = ['countries', 'admin1'], bool $sqlite = false): GeonamesRepository
{
    $fixture = new FixtureSnapshot;
    $store = new SnapshotStore($test->temporaryDirectory());
    (new SnapshotFetcher(new FakeTransport($fixture->routes()), $store, $fixture->pin(), 'https://data.test/geonames/v1'))
        ->fetch($countries, $datasets, $sqlite);

    return new GeonamesRepository(new StoreSource($store, $fixture->pin()));
}

it('finds a country by alpha-2, alpha-3 or numeric code, in any case', function () {
    $geonames = repositoryWith($this);

    expect($geonames->countries())->toHaveCount(3)
        ->and($geonames->country('th')?->iso3())->toBe('THA')
        ->and($geonames->country('USA')?->iso2())->toBe('US')
        ->and($geonames->country('156')?->iso2())->toBe('CN')
        ->and($geonames->country(764)?->iso2())->toBe('TH')
        ->and($geonames->country('ZZ'))->toBeNull()
        ->and(fn () => $geonames->countryOrFail('ZZ'))->toThrow(NotFoundException::class);
});

it('reads names by language and falls back to the default name', function () {
    $th = repositoryWith($this)->countryOrFail('TH');

    expect($th->name('th'))->toBe('ไทย')
        ->and($th->name('en'))->toBe('Thailand')
        ->and($th->name('ja'))->toBe('Thailand')
        ->and($th->name('ja', fallback: false))->toBeNull()
        ->and($th->nameTh)->toBe('ไทย')
        ->and($th->callingCode)->toBe('66')
        ->and($th->currency())->toBe(['code' => 'THB', 'name' => 'THB'])
        ->and($th->toArray()['iso2'])->toBe('TH');
});

it('reads countries, kinds and time zones from the bundled data when the store has none', function () {
    $fixture = new FixtureSnapshot;
    $bundled = $this->temporaryDirectory();
    foreach (['countries.json', 'kinds.json', 'time_zones.json'] as $file) {
        file_put_contents($bundled.'/'.$file, $fixture->files[$file]);
    }
    $source = new StoreSource(new SnapshotStore($this->temporaryDirectory()), $fixture->pin(), bundledDirectory: $bundled);
    $geonames = new GeonamesRepository($source);

    expect($geonames->countries())->toHaveCount(3)
        ->and($geonames->timeZones('TH'))->toBe(['Asia/Bangkok'])
        ->and($geonames->kinds())->toHaveCount(2);
});

it('lists admin1 of one country and finds one by its code', function () {
    $geonames = repositoryWith($this);

    expect($geonames->admin1('TH')->map->geonamesCode()->all())->toBe(['TH.50', 'TH.40'])
        ->and($geonames->admin1Code('TH', '40')?->name('th'))->toBe('กรุงเทพมหานคร')
        ->and($geonames->admin1Code('TH', '40')?->iso31662())->toBe('TH-10')
        ->and($geonames->admin1('US')->first()?->iso31662())->toBe('US-CA');
});

it('lists cities, hides abolished ones, and reads an unknown kind as other', function () {
    $geonames = repositoryWith($this, ['TH', 'US'], ['cities']);

    expect($geonames->cities('TH')->map->name()->all())->toBe(['Bangkok', 'Chiang Mai'])
        ->and($geonames->withAbolished()->cities('TH')->map->name()->all())->toBe(['Bangkok', 'Chiang Mai', 'Don Sak'])
        ->and($geonames->withAbolished()->cities('TH')->last()?->isAbolished())->toBeTrue()
        ->and($geonames->cities('US')->last()?->kind())->toBe('other')
        ->and($geonames->cities('TH', admin1: '50')->map->name()->all())->toBe(['Chiang Mai'])
        ->and($geonames->cities('TH', minPopulation: 1_000_000)->map->name()->all())->toBe(['Bangkok'])
        ->and($geonames->cities('TH')->first()?->isCapital())->toBeTrue();
});

it('ignores fields a newer schema minor adds', function () {
    $city = repositoryWith($this, ['TH'], ['cities'])->cities('TH')->first();

    expect($city)->toBeInstanceOf(City::class)
        ->and($city->population())->toBe(5104476)
        ->and($city->populationTier())->toBe(15000)
        ->and($city->elevationM())->toBeNull()
        ->and($city->demM())->toBe(10);
});

it('finds a city by id among the countries that were fetched, never the network', function () {
    $geonames = repositoryWith($this, ['TH', 'US'], ['cities']);

    expect($geonames->city(5368361)?->name())->toBe('Los Angeles')
        ->and($geonames->city(1609350, 'TH')?->name())->toBe('Bangkok')
        ->and($geonames->city(1609350, 'US'))->toBeNull()
        ->and($geonames->city(1116760))->toBeNull('abolished rows are hidden')
        ->and($geonames->withAbolished()->city(1116760)?->isAbolished())->toBeTrue();

    expect(fn () => repositoryWith($this)->city(1609350))->toThrow(NotFetchedException::class, 'geonames fetch');
});

it('names the fetch command when a country was not fetched', function () {
    $geonames = repositoryWith($this, ['TH'], ['cities']);

    expect(fn () => $geonames->cities('US'))->toThrow(NotFetchedException::class, 'geonames fetch US --datasets=cities')
        ->and(fn () => $geonames->admin1('TH'))->toThrow(NotFetchedException::class, 'admin1');
});

it('returns every postal row of a code, with its precision', function () {
    $geonames = repositoryWith($this, ['US', 'CN'], ['postal_codes']);

    $us = $geonames->postalCodes('US', '90210');
    expect($us)->toHaveCount(2)
        ->and($us->every->isFull())->toBeTrue()
        ->and($geonames->postalCodes('us'))->toHaveCount(2)
        ->and($geonames->postalCodes('US', '00000'))->toHaveCount(0);

    $cn = $geonames->postalCodes('CN', '10');
    expect($cn)->toHaveCount(1)
        ->and($cn->first()?->precision())->toBe('partial')
        ->and($cn->first()?->isFull())->toBeFalse();
});

it('says where Thai postal codes are instead of returning nothing', function () {
    $geonames = repositoryWith($this, ['TH'], ['cities', 'postal_codes']);

    expect(fn () => $geonames->postalCodes('TH'))->toThrow(PostalCodesElsewhereException::class, 'farzai/thailand-address');
});

it('searches cities by name in any language, prefix matches first then larger cities, within the fetched countries', function () {
    $geonames = repositoryWith($this, ['TH', 'US'], ['cities']);

    expect($geonames->search('chiang')->map->name()->all())->toBe(['Chiang Mai'])
        ->and($geonames->search('เชียง', 'TH')->map->name()->all())->toBe(['Chiang Mai'])
        ->and($geonames->search('an', 'US')->map->name()->all())->toBe(['Los Angeles', 'San Francisco'])
        ->and($geonames->search('  ')->all())->toBe([])
        ->and($geonames->search('bangkok', limit: 1))->toHaveCount(1);
});

it('finds the nearest cities from the SQLite file, nearest first, exactly', function () {
    $geonames = repositoryWith($this, ['TH'], [], sqlite: true);

    $near = $geonames->nearest(13.7, 100.5, 'TH', limit: 2);

    expect($near->map->name()->all())->toBe(['Bangkok', 'Pattaya'])
        ->and($near->first()?->toArray()['distance_km'])->toBeLessThan(10.0)
        ->and($near->first()?->nameTh)->toBe('กรุงเทพมหานคร')
        ->and($near->first()?->countryCode())->toBe('TH')
        ->and($geonames->nearest(9.3, 99.7, 'TH', limit: 5)->map->name()->all())->not->toContain('Don Sak', 'abolished cities are not returned')
        ->and($geonames->nearest(0.0, 0.0, 'TH', limit: 1)->first()?->name())->not->toBeNull('a far point still finds the nearest city');
});

it('needs the SQLite file for nearest and says how to get it', function () {
    expect(fn () => repositoryWith($this, ['TH'], ['cities'])->nearest(13.7, 100.5, 'TH'))
        ->toThrow(NotFetchedException::class, 'geonames fetch TH --sqlite');
});

it('works over arrays as a test fake, through the static facade', function () {
    Geonames::swap(GeonamesRepository::fromArrays(
        countries: [['iso2' => 'XX', 'iso3' => 'XXX', 'iso_numeric' => '999', 'name' => 'Testland', 'names' => ['th' => 'เทสต์']]],
        cities: ['xx' => [['geoname_id' => 1, 'name' => 'Test City', 'names' => [], 'lat' => 1.0, 'lon' => 2.0, 'population' => 10]]],
    ));

    try {
        expect(Geonames::country('xx')?->nameTh)->toBe('เทสต์')
            ->and(Geonames::cities('XX')->first()?->name())->toBe('Test City')
            ->and(Geonames::nearest(1.0, 2.0, 'XX', 1)->first()?->name())->toBe('Test City')
            ->and(Geonames::countries())->toHaveCount(1);
    } finally {
        Geonames::swap(null);
    }
});

it('refuses a file with a line longer than the cap', function () {
    $path = $this->temporaryDirectory().'/long.ndjson.gz';
    file_put_contents($path, gzencode('{"schema_version":"1.0"}'."\n".str_repeat('a', Reader::MAX_LINE_BYTES + 10)."\n"));

    expect(fn () => iterator_to_array(Reader::ndjson($path)))
        ->toThrow(GeonamesException::class, 'longer than');
});
