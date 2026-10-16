<?php

declare(strict_types=1);

use Database\Seeders\GeonamesSeeder;
use Farzai\Geonames\Exceptions\NotFetchedException;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Laravel\Seeding\Importer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Run the publishable migration the way an app would after vendor:publish.
    $dir = sys_get_temp_dir().'/geonames-migrations-'.bin2hex(random_bytes(4));
    mkdir($dir);
    copy(dirname(__DIR__, 2).'/database/migrations/create_geonames_tables.php.stub', $dir.'/2030_01_01_000000_create_geonames_tables.php');
    $this->loadMigrationsFrom($dir);
    $this->artisan('migrate')->assertSuccessful();
    $this->migrations = $dir;
});

afterEach(function () {
    array_map('unlink', glob($this->migrations.'/*') ?: []);
    @rmdir($this->migrations);
});

it('creates the six tables with the keys the snapshot supports', function () {
    foreach (config('geonames.tables') as $table) {
        expect(Schema::hasTable($table))->toBeTrue($table);
    }
    $indexes = fn (string $table) => collect(Schema::getIndexes($table));

    expect($indexes('geonames_countries')->pluck('columns')->flatten())->toContain('iso2')
        ->and($indexes('geonames_postal_codes')->where('unique', true)->where('primary', false))->toHaveCount(0)
        ->and($indexes('geonames_admin1')->where('unique', true)->where('primary', false))->toHaveCount(0)
        ->and($indexes('geonames_admin2')->where('unique', true)->where('primary', false))->toHaveCount(0)
        ->and($indexes('geonames_alternate_names')->firstWhere('name', 'geonames_alternate_names_geoname_lang_index')['columns'])
        ->toBe(['geoname_id', 'lang']);
});

it('imports fetched data, including abolished rows with their status', function () {
    $this->fetch(['TH', 'US', 'CN'], ['countries', 'admin1', 'admin2', 'cities', 'postal_codes', 'alternate_names']);

    $this->artisan('geonames:seed', ['--datasets' => 'countries,admin1,admin2,cities,postal_codes,alternate_names'])->assertSuccessful();

    expect(DB::table('geonames_countries')->count())->toBe(3)
        ->and(DB::table('geonames_admin1')->count())->toBe(3)
        ->and(DB::table('geonames_admin2')->count())->toBe(2)
        ->and(DB::table('geonames_cities')->count())->toBe(5)
        ->and(DB::table('geonames_cities')->where('status', 'abolished')->value('geoname_id'))->toBe(1116760)
        ->and(DB::table('geonames_alternate_names')->count())->toBe(1);

    $city = DB::table('geonames_cities')->where('geoname_id', 1609350)->first();
    expect(json_decode($city->names, true)['th'])->toBe('กรุงเทพมหานคร')
        ->and((bool) $city->is_capital)->toBeTrue()
        ->and($city->kind)->toBe('capital');
});

it('keeps every postal row of a code: one code serves many places', function () {
    $this->fetch(['US'], ['countries', 'postal_codes']);

    $this->artisan('geonames:seed', ['countries' => ['US'], '--datasets' => 'postal_codes'])->assertSuccessful();

    expect(DB::table('geonames_postal_codes')->where('postal_code', '90210')->count())->toBe(2)
        ->and(DB::table('geonames_postal_codes')->where('postal_code', '90210')->pluck('place_name')->sort()->values()->all())
        ->toBe(['Beverly Hills', 'Beverly Hills (annex)']);
});

it('writes a postal row that repeats byte for byte once, and says so', function () {
    $this->fetch(['CN'], ['countries', 'postal_codes']);

    $this->artisan('geonames:seed', ['countries' => ['CN'], '--datasets' => 'postal_codes'])
        ->expectsOutputToContain('1 identical postal rows skipped')
        ->assertSuccessful();

    expect(DB::table('geonames_postal_codes')->where('country_code', 'CN')->count())->toBe(2);
});

it('drops a postal code that a later snapshot no longer lists', function () {
    $this->fetch(['CN'], ['countries', 'postal_codes']);
    DB::table('geonames_postal_codes')->insert(['country_code' => 'CN', 'postal_code' => '999999', 'place_name' => 'Removed', 'precision' => 'partial', 'source' => 'geonames']);
    DB::table('geonames_postal_codes')->insert(['country_code' => 'US', 'postal_code' => '11111', 'place_name' => 'Other country', 'precision' => 'full', 'source' => 'geonames']);

    $this->artisan('geonames:seed', ['countries' => ['CN'], '--datasets' => 'postal_codes'])->assertSuccessful();

    expect(DB::table('geonames_postal_codes')->where('postal_code', '999999')->count())->toBe(0)
        ->and(DB::table('geonames_postal_codes')->where('country_code', 'US')->count())->toBe(1);
});

it('lets an abolished and a replacement admin row share a geonames code', function () {
    $row = ['country_code' => 'ZZ', 'admin1_code' => '01', 'name' => 'Old', 'ascii_name' => 'Old', 'names' => '{}', 'geonames_code' => 'ZZ.01'];
    DB::table('geonames_admin1')->insert([$row + ['geoname_id' => 1, 'status' => 'abolished'], array_merge($row, ['geoname_id' => 2, 'name' => 'New', 'status' => 'active'])]);

    expect(DB::table('geonames_admin1')->where('geonames_code', 'ZZ.01')->count())->toBe(2);
});

it('stores unknown admin codes as empty strings so a row has the same shape everywhere', function () {
    $this->fetch(['CN'], ['countries', 'postal_codes']);

    $this->artisan('geonames:seed', ['countries' => ['CN'], '--datasets' => 'postal_codes'])->assertSuccessful();
    $row = DB::table('geonames_postal_codes')->first();

    expect($row->admin2_code)->toBe('')->and($row->admin3_code)->toBe('')->and($row->precision)->toBe('partial');
});

it('can be run again without changing a row count', function () {
    $this->fetch(['TH', 'US'], ['countries', 'admin1', 'admin2', 'cities', 'postal_codes', 'alternate_names']);
    $options = ['--datasets' => 'countries,admin1,admin2,cities,postal_codes,alternate_names'];

    $this->artisan('geonames:seed', $options)->assertSuccessful();
    $before = collect(config('geonames.tables'))->map(fn ($t) => DB::table($t)->count())->all();
    $this->artisan('geonames:seed', $options)->assertSuccessful();

    expect(collect(config('geonames.tables'))->map(fn ($t) => DB::table($t)->count())->all())->toBe($before);
});

it('replaces the alternate names of a country instead of stacking them', function () {
    $this->fetch(['US'], ['countries', 'alternate_names']);
    DB::table('geonames_alternate_names')->insert(['country_code' => 'US', 'geoname_id' => 5368361, 'lang' => 'xx', 'name' => 'stale', 'preferred' => 0, 'short' => 0, 'colloquial' => 0, 'historic' => 0]);
    DB::table('geonames_alternate_names')->insert(['country_code' => 'TH', 'geoname_id' => 1, 'lang' => 'xx', 'name' => 'other country', 'preferred' => 0, 'short' => 0, 'colloquial' => 0, 'historic' => 0]);

    $this->artisan('geonames:seed', ['countries' => ['US'], '--datasets' => 'alternate_names'])->assertSuccessful();

    expect(DB::table('geonames_alternate_names')->where('country_code', 'US')->pluck('name')->all())->toBe(['ลอสแอนเจลิส'])
        ->and(DB::table('geonames_alternate_names')->where('country_code', 'TH')->count())->toBe(1);
});

it('updates a changed row in place', function () {
    $this->fetch(['TH'], ['countries', 'cities']);
    DB::table('geonames_cities')->insert(['geoname_id' => 1609350, 'country_code' => 'TH', 'name' => 'Old', 'ascii_name' => 'Old', 'names' => '{}', 'lat' => 1, 'lon' => 1, 'kind' => 'city', 'feature_class' => 'P', 'feature_code' => 'PPL', 'population' => 1]);

    (new Importer(app(GeonamesRepository::class), DB::connection(), config('geonames.tables')))->import(['TH'], ['cities']);

    expect(DB::table('geonames_cities')->where('geoname_id', 1609350)->value('name'))->toBe('Bangkok')
        ->and(DB::table('geonames_cities')->where('country_code', 'TH')->count())->toBe(3);
});

it('seeds through the published seeder class too', function () {
    $this->fetch(['TH'], ['countries', 'admin1', 'admin2', 'cities']);
    require_once dirname(__DIR__, 2).'/database/seeders/GeonamesSeeder.php';

    $this->seed(GeonamesSeeder::class);

    expect(DB::table('geonames_cities')->where('country_code', 'TH')->count())->toBe(3)
        ->and(DB::table('geonames_countries')->count())->toBe(3);
});

it('lets the not-fetched error surface, naming the command to run', function () {
    expect(fn () => $this->withoutExceptionHandling()->artisan('geonames:seed', ['countries' => ['TH'], '--datasets' => 'cities'])->run())
        ->toThrow(NotFetchedException::class, 'geonames fetch TH --datasets=cities');
});

it('fetches through the artisan command, with the fixture served by the fake transport', function () {
    $this->artisan('geonames:fetch', ['countries' => ['TH'], '--datasets' => 'cities'])
        ->expectsOutputToContain('downloaded  by-country/TH/cities.ndjson.gz')->assertSuccessful();

    $this->artisan('geonames:fetch', ['countries' => ['../x']])->expectsOutputToContain('alpha-2')->assertFailed();
});

it('removes repeated postal rows in the database, however far apart they are', function () {
    $row = fn (int $i) => ['country_code' => 'GB', 'postal_code' => 'AB'.$i, 'place_name' => 'Place '.$i, 'admin1_code' => null, 'lat' => 51.5, 'lon' => -0.1, 'precision' => 'full'];
    $rows = array_map($row, range(1, 3000));
    $rows[] = $row(1);       // far from its twin
    $rows[] = $row(2999);
    $rows[] = $row(2999);
    $rows[] = $row(7);
    $rows[] = ['lat' => 52.0] + $row(7); // differs in a column: kept

    $importer = new Importer(
        GeonamesRepository::fromArrays(postalCodes: ['GB' => $rows]),
        DB::connection(),
        config('geonames.tables'),
    );
    $counts = $importer->import(['GB'], ['postal_codes']);

    expect(DB::table('geonames_postal_codes')->where('country_code', 'GB')->count())->toBe(3001)
        ->and($counts['postal_codes'])->toBe(3001)
        ->and($importer->duplicatesDropped)->toBe(4);
});
