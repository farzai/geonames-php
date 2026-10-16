<?php

declare(strict_types=1);

use Farzai\Geonames\Exceptions\NotFoundException;
use Farzai\Geonames\Laravel\Casts\AsAdmin1;
use Farzai\Geonames\Laravel\Casts\AsCity;
use Farzai\Geonames\Laravel\Casts\AsCountry;
use Farzai\Geonames\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CastedCustomer extends Model
{
    protected $table = 'customers';

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['country' => AsCountry::class, 'region' => AsAdmin1::class, 'city_id' => AsCity::class];
    }
}

beforeEach(function () {
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('country')->nullable();
        $table->string('region')->nullable();
        $table->unsignedBigInteger('city_id')->nullable();
    });
    $this->fetch(['TH', 'US'], ['countries', 'admin1', 'cities']);
});

it('stores a country as its alpha-2 code and reads the model back', function () {
    $customer = CastedCustomer::create(['country' => 'tha']);

    expect(DB::table('customers')->value('country'))->toBe('TH')
        ->and($customer->fresh()->country)->toBeInstanceOf(Country::class)
        ->and($customer->fresh()->country->nameTh)->toBe('ไทย');

    $customer->country = $customer->country; // a Country model sets too
    $customer->country = 840;                // so does a numeric code
    $customer->save();
    expect(DB::table('customers')->value('country'))->toBe('US');
});

it('throws on an unknown code when set, gives null when read back, and clears on blank', function () {
    expect(fn () => CastedCustomer::create(['country' => 'ZZ']))->toThrow(NotFoundException::class);

    DB::table('customers')->insert(['country' => 'ZZ', 'region' => 'ZZ.1', 'city_id' => 1]);
    $stale = CastedCustomer::first();
    expect($stale->country)->toBeNull()
        ->and($stale->region)->toBeNull()
        ->and($stale->city_id)->toBeNull();

    $customer = CastedCustomer::create(['country' => 'TH']);
    $customer->country = '';
    $customer->save();
    expect(DB::table('customers')->where('id', $customer->id)->value('country'))->toBeNull();
});

it('stores an admin1 as its GeoNames code and a city as its geoname id', function () {
    $customer = CastedCustomer::create(['country' => 'TH', 'region' => 'TH.50', 'city_id' => 1153671]);
    $row = DB::table('customers')->where('id', $customer->id)->first();

    expect($row->region)->toBe('TH.50')
        ->and((int) $row->city_id)->toBe(1153671)
        ->and($customer->fresh()->region->name('th'))->toBe('เชียงใหม่')
        ->and($customer->fresh()->city_id->name('en'))->toBe('Chiang Mai');
});

it('refuses a code of the wrong shape and one that does not exist', function () {
    expect(fn () => CastedCustomer::create(['region' => '50']))->toThrow(NotFoundException::class)
        ->and(fn () => CastedCustomer::create(['region' => 'TH.99']))->toThrow(NotFoundException::class)
        ->and(fn () => CastedCustomer::create(['city_id' => 42]))->toThrow(NotFoundException::class)
        ->and(fn () => CastedCustomer::create(['city_id' => 'abc']))->toThrow(InvalidArgumentException::class);
});
