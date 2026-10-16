<?php

declare(strict_types=1);

use Farzai\Geonames\Laravel\Rules\GeonamesAdmin1;
use Farzai\Geonames\Laravel\Rules\GeonamesCity;
use Farzai\Geonames\Laravel\Rules\GeonamesCountry;
use Farzai\Geonames\Laravel\Rules\GeonamesPostalCode;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** @return array<string, list<string>> the validator's errors */
function validate(array $data, array $rules): array
{
    return Validator::make($data, $rules)->errors()->toArray();
}

it('validates a country by alpha-2, alpha-3 or numeric code', function () {
    $this->fetch();

    foreach (['TH', 'th', 'THA', '764', 840] as $ok) {
        expect(validate(['c' => $ok], ['c' => 'geonames_country']))->toBe([], (string) $ok);
    }
    foreach (['ZZ', 'Thailand', ['TH']] as $bad) {
        expect(validate(['c' => $bad], ['c' => 'geonames_country']))->toHaveKey('c');
    }
    expect(validate(['c' => 'ZZ'], ['c' => [Rule::geonamesCountry()]]))->toHaveKey('c')
        ->and(validate(['c' => 'TH'], ['c' => [new GeonamesCountry]]))->toBe([]);
});

it('validates an admin1 code inside the selected country, one error per mistake', function () {
    $this->fetch();

    $rules = ['country' => 'required|geonames_country', 'admin1' => 'required|geonames_admin1:country'];
    expect(validate(['country' => 'TH', 'admin1' => '50'], $rules))->toBe([])
        ->and(validate(['country' => 'th', 'admin1' => '50'], $rules))->toBe([])
        ->and(validate(['country' => 'US', 'admin1' => '50'], $rules))->toHaveKey('admin1')
        ->and(validate(['country' => 'ZZ', 'admin1' => '50'], $rules))->toHaveKey('country')->not->toHaveKey('admin1')
        ->and(validate(['admin1' => '50'], ['admin1' => 'geonames_admin1:country']))->toBe([])
        ->and(validate(['admin1' => 'TH.50'], ['admin1' => 'geonames_admin1']))->toBe([])
        ->and(validate(['admin1' => '50'], ['admin1' => 'geonames_admin1']))->toHaveKey('admin1');
});

it('pairs wildcard arrays by position', function () {
    $this->fetch();

    $data = ['addresses' => [['country' => 'TH', 'admin1' => '50'], ['country' => 'US', 'admin1' => 'CA'], ['country' => 'US', 'admin1' => '50']]];
    $errors = validate($data, ['addresses.*.admin1' => 'geonames_admin1:addresses.*.country']);

    expect(array_keys($errors))->toBe(['addresses.2.admin1']);
});

it('says the data is not downloaded instead of throwing out of validation', function () {
    // Countries are bundled, admin1 is not: nothing was fetched.
    $errors = validate(['c' => 'TH', 'a' => '50'], ['c' => 'geonames_country', 'a' => 'geonames_admin1:c']);

    expect($errors)->not->toHaveKey('c')
        ->and($errors['a'][0])->toContain('geonames:fetch');
});

it('validates a city id in the selected country, among the countries that were fetched', function () {
    $this->fetch(['TH', 'US'], ['countries', 'cities']);

    expect(validate(['country' => 'TH', 'city' => 1609350], ['city' => 'geonames_city:country']))->toBe([])
        ->and(validate(['country' => 'US', 'city' => 1609350], ['city' => 'geonames_city:country']))->toHaveKey('city')
        ->and(validate(['city' => '5368361'], ['city' => 'geonames_city']))->toBe([])
        ->and(validate(['city' => 'abc'], ['city' => 'geonames_city']))->toHaveKey('city')
        ->and(validate(['city' => 1116760], ['city' => 'geonames_city']))->toHaveKey('city');
});

it('checks a postal code against the country pattern', function () {
    $this->fetch();

    $rules = ['country' => 'geonames_country', 'postal' => 'geonames_postal_code:country'];
    expect(validate(['country' => 'US', 'postal' => '90210'], $rules))->toBe([])
        ->and(validate(['country' => 'US', 'postal' => '90210-1234'], $rules))->toBe([])
        ->and(validate(['country' => 'US', 'postal' => 'ABCDE'], $rules))->toHaveKey('postal')
        ->and(validate(['country' => 'CN', 'postal' => '100000'], $rules))->toBe([])
        ->and(validate(['country' => 'CN', 'postal' => '10'], $rules))->toHaveKey('postal')
        ->and(validate(['country' => 'TH', 'postal' => 'anything goes without a pattern'], $rules))->toBe([])
        ->and(validate(['country' => 'ZZ', 'postal' => '1'], $rules))->toHaveKey('country')->not->toHaveKey('postal')
        ->and(validate(['country' => 'US', 'postal' => ''], ['country' => 'required', 'postal' => 'required|geonames_postal_code:country']))->toHaveKey('postal');
});

it('can require the code to exist, never treating a partial prefix as a complete code', function () {
    $this->fetch(['US', 'CN', 'TH'], ['countries', 'postal_codes']);
    $rule = fn (string $country) => ['postal' => [GeonamesPostalCode::make()->forCountryField('country')->mustExist()]];

    expect(validate(['country' => 'US', 'postal' => '90210'], $rule('US')))->toBe([])
        ->and(validate(['country' => 'US', 'postal' => '90211'], $rule('US')))->toHaveKey('postal')
        ->and(validate(['country' => 'CN', 'postal' => '100000'], $rule('CN')))->toBe([], 'a listed prefix covers the full code')
        ->and(validate(['country' => 'CN', 'postal' => '101234'], $rule('CN')))->toBe([], 'zero-padded listed codes are prefixes without the padding')
        ->and(validate(['country' => 'CN', 'postal' => '231199'], $rule('CN')))->toBe([])
        ->and(validate(['country' => 'CN', 'postal' => '231200'], $rule('CN')))->toHaveKey('postal')
        ->and(validate(['country' => 'CN', 'postal' => '200000'], $rule('CN')))->toHaveKey('postal')
        ->and(validate(['country' => 'TH', 'postal' => '10200'], $rule('TH')))->toHaveKey('postal');

    expect(validate(['country' => 'TH', 'postal' => '10200'], $rule('TH'))['postal'][0])->toContain('farzai/thailand-address');
});

it('names the fetch command when mustExist has no postal file', function () {
    $this->fetch(['US'], ['countries']);

    $errors = validate(['country' => 'US', 'postal' => '90210'], ['postal' => [GeonamesPostalCode::make()->forCountryField('country')->mustExist()]]);

    expect($errors['postal'][0])->toContain('geonames:fetch');
});

it('answers in Thai when the app locale is th', function () {
    $this->fetch();
    app()->setLocale('th');

    expect(validate(['c' => 'ZZ'], ['c' => 'geonames_country'])['c'][0])->toContain('รหัสประเทศ');
});

it('exposes rule objects for every rule', function () {
    $this->fetch(['TH'], ['countries', 'admin1', 'cities']);

    expect(validate(['country' => 'TH', 'admin1' => '50', 'city' => 1153671], [
        'country' => [Rule::geonamesCountry()],
        'admin1' => [Rule::geonamesAdmin1()->forCountryField('country')],
        'city' => [Rule::geonamesCity()->forCountryField('country')],
    ]))->toBe([])
        ->and(new GeonamesAdmin1)->toBeInstanceOf(ValidationRule::class)
        ->and(new GeonamesCity)->toBeInstanceOf(DataAwareRule::class);
});
