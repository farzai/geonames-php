# GeoNames data for PHP and Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/farzai/geonames.svg?style=flat-square)](https://packagist.org/packages/farzai/geonames)
[![Tests](https://img.shields.io/github/actions/workflow/status/farzai/geonames-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/farzai/geonames-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/farzai/geonames.svg?style=flat-square)](https://packagist.org/packages/farzai/geonames)

Countries, first- and second-level administrative divisions, cities and postal codes
from [GeoNames](https://www.geonames.org), read from the verified snapshots Farzai
publishes at <https://data.farzai.com/geonames/>. Plain PHP, with first-class Laravel
support.

- An API that reads like Laravel: Laravel collections, `*OrFail` lookups, models with
  names in many languages (Thai and English first).
- No network at runtime. One command downloads exactly the countries you ask for, and
  checks every file against the manifest's sha256 before it keeps it.
- Validation rules, Eloquent casts, migrations and a seeder for Laravel.
- The data is pinned: this release reads snapshot `2026-10-16.1` and never follows
  `latest` by itself.

**Upgrading from 2.x:** 3.0 is a rewrite. The downloader, the converters (including MongoDB)
and the `--feature-class` option are gone. See [UPGRADING.md](UPGRADING.md).

## Requirements

- PHP 8.3 or newer, with `ext-json` and `ext-zlib`
- `illuminate/collections` 12 or 13, installed with the package
- `ext-pdo_sqlite` for `nearest()` (optional)
- Laravel 12 or 13 for the Laravel integration (optional)

## Install

```sh
composer require farzai/geonames
```

Countries, the feature-kind table and time zones ship with the package (about 310 KB), so
`Geonames::countries()` works at once. Everything else is fetched on demand.

## Fetch data

```sh
vendor/bin/geonames fetch TH US                          # admin1, admin2 and cities (the default)
vendor/bin/geonames fetch GB --datasets=cities,postal_codes
vendor/bin/geonames fetch TH --sqlite                    # the SQLite file, for nearest()
vendor/bin/geonames fetch TH --datasets=all --snapshot=latest
```

| Option | Meaning |
|---|---|
| `--datasets=a,b` | `admin1`, `admin2`, `cities`, `alternate_names`, `postal_codes`, or `all` (default `admin1,admin2,cities`) |
| `--sqlite` | also fetch each country's SQLite file |
| `--snapshot=ID` | a snapshot id, or `latest` (default: the snapshot this release is pinned to) |
| `--path=DIR` | the store directory (default `$GEONAMES_PATH`, else `~/.cache/farzai-geonames`) |

- **Verified.** The manifest is checked against the pinned (or `latest.json`) sha256, and
  every file against the manifest's sha256 and size. A file is written to `<name>.part`
  and renamed only when both match, so a failed download leaves nothing behind.
- **Pinned.** `latest.json` is read only with `--snapshot=latest`. A snapshot that
  `snapshots.json` lists as withdrawn is refused, and so is a different schema major
  version (a newer minor reads fine).
  `--snapshot=latest` trusts `data.farzai.com` end to end; the bundled pin is the anchor
  for everything else. Only https is fetched (plain http only for localhost).
- **Country codes** must be two letters; anything else (`../../x`, `all`) is refused.
- A file the snapshot does not have is reported, not an error. Thailand has no postal
  file: use [`farzai/thailand-address`](https://github.com/parsilver/thailand-provinces-php),
  the authority for Thai postal codes. `Geonames::postalCodes('TH')` throws
  `PostalCodesElsewhereException` and says so.

Reading never touches the network. If the data you ask for is not in the store, you get
a `NotFetchedException` that names the command to run.

## Plain PHP (and everything else)

```php
use Farzai\Geonames\Geonames;

Geonames::countries();                          // Collection<Country>, 252, bundled
$th = Geonames::country('TH');                  // by ISO alpha-2, alpha-3 or numeric; ?Country
Geonames::countryOrFail('tha');                 // or NotFoundException
$th->name('th');                                // 'ไทย'; ->name('en'); ->name('ja') falls back to the default name
$th->nameTh;                                    // property or method, as in thailand-address
$th->currency();                                // ['code' => 'THB', 'name' => 'Baht']
$th->callingCode; $th->timeZones(); $th->postalCodeRegex();

Geonames::admin1('TH');                         // Collection<Admin1>, keyed by geoname_id and "TH.40"
Geonames::admin1Code('TH', '40')->iso31662();   // 'TH-10' (nullable, never a join key)
Geonames::admin2('TH', admin1: '50');
Geonames::cities('TH', minPopulation: 100_000); // Collection<City>
Geonames::city(1609350, 'TH');                  // by geoname_id, fetched countries only
Geonames::search('chiang', 'TH');               // names in any selected language, prefix first
Geonames::postalCodes('US', '90210');           // every row of a code, each with ->precision()
Geonames::nearest(13.75, 100.5, 'TH', limit: 5);// needs the SQLite file; each result has distance_km
Geonames::timeZones('TH');                      // ['Asia/Bangkok']
```

- **Collections** are Laravel collections (`Illuminate\Support\Collection`).
- **Models** (`Country`, `Admin1`, `Admin2`, `City`, `PostalCode`) are read-only and implement
  `Arrayable` and `JsonSerializable`. `toArray()` keeps the snapshot's snake_case field
  names, including fields a newer schema minor adds.
- **Null means unknown.** An unknown `kind` reads as `other`. Rows with `status: abolished`
  are hidden unless you ask: `Geonames::withAbolished()->cities('TH')`.
- **Names** are `{bcp47: name}` and hold only the languages the pipeline selected (th and
  en first). Each is the preferred name of the language, or the first current name that
  is not historic, not colloquial and has no end date, so a name is not always a
  "preferred" one. `name('xx')` falls back to GeoNames' default name.
- **Postal codes** have no `geoname_id`; one code serves many places, so
  `postalCodes('US', '90210')` returns a collection. `->precision()` is `full` or
  `partial`: for CL, CN, IE, MT, AR and BR only the first characters of a code are known,
  so never treat a `partial` row as a complete code. GB and NL include full unit codes;
  Canada has the forward sortation area only.
- **Nearest** reads the country's SQLite file through its R*Tree and is exact: the
  search box doubles until the answer cannot change. It needs `pdo_sqlite` and the file
  (`fetch TH --sqlite`), and is limited to one country.

### Testing with a fake

```php
use Farzai\Geonames\Geonames;
use Farzai\Geonames\GeonamesRepository;

Geonames::swap(GeonamesRepository::fromArrays(
    countries: [['iso2' => 'XX', 'iso3' => 'XXX', 'iso_numeric' => '999', 'name' => 'Testland', 'names' => ['th' => 'เทสต์']]],
    cities: ['XX' => [['geoname_id' => 1, 'name' => 'Test City', 'names' => [], 'lat' => 1.0, 'lon' => 2.0, 'population' => 10]]],
));

Geonames::swap(null); // back to the real data
```

In Laravel, `Geonames::swap()` also rebinds `GeonamesRepository` in the container, which the
facade, the rules and the casts read.

## Laravel

Package discovery registers the service provider and the `Geonames` facade alias.

```sh
php artisan geonames:fetch TH US --datasets=admin1,admin2,cities
```

### Validation

```php
use Illuminate\Validation\Rule;

$request->validate([
    'country'     => ['required', Rule::geonamesCountry()],
    'admin1'      => ['required', Rule::geonamesAdmin1()->forCountryField('country')],
    'city_id'     => ['required', Rule::geonamesCity()->forCountryField('country')],
    'postal_code' => ['required', Rule::geonamesPostalCode()->forCountryField('country')],
]);
```

String rules do the same: `geonames_country`, `geonames_admin1:country`, `geonames_city:country`,
`geonames_postal_code:country`.

| Rule | Passes when |
|---|---|
| `geonames_country` | the value is an ISO 3166-1 alpha-2, alpha-3 or numeric code |
| `geonames_admin1:field` | the value is an admin1 code (`50`) of the country in `field`; without a field it must be `TH.50` |
| `geonames_city:field` | the value is a city `geoname_id`, in the country in `field` when given |
| `geonames_postal_code:field` | the value matches the country's postal-code pattern; `->mustExist()` also requires a row in the fetched postal file |

- **One error per mistake.** A parent field that is empty or invalid passes the child's
  check; the parent's own rule reports it. Wildcards pair by position:
  `'addresses.*.admin1' => 'geonames_admin1:addresses.*.country'`.
- **Data not fetched** is a failed check whose message names `geonames:fetch`, never an
  exception out of validation.
- **`mustExist()` and partial countries.** A code in CL, CN, IE, MT, AR or BR matches when it
  starts with a listed prefix. CN, CL and BR list codes zero-padded to full length (CN
  `230000`, BR `69945-000`); the padding is not part of the prefix, so `231199` passes when
  `231100` is listed. Spaces and case are ignored (GB `SW1A 1AA`). The file is streamed and
  the check stops at the first match. For Thailand the message points to `farzai/thailand-address`.
- Messages come in English and Thai (`geonames::validation`). Publish them with
  `php artisan vendor:publish --tag=geonames-lang`.

### Eloquent casts

```php
use Farzai\Geonames\Laravel\Casts\AsAdmin1;
use Farzai\Geonames\Laravel\Casts\AsCity;
use Farzai\Geonames\Laravel\Casts\AsCountry;

protected function casts(): array
{
    return ['country' => AsCountry::class, 'region' => AsAdmin1::class, 'city_id' => AsCity::class];
}

$customer->country = 'tha';                 // stores 'TH'
$customer->country->name('th');             // 'ไทย'
$customer->region = 'TH.50';                // stores the GeoNames code
$customer->city_id = 1153671;               // stores the geoname_id
```

- **Unknown codes.** Setting one throws `NotFoundException`; reading one back gives `null`, so a
  stored code that stops existing never breaks a row read.
- **Blank input.** Setting `''` or `null` clears the column.
- `AsAdmin1` and `AsCity` need the admin1 or cities data to be fetched.

### Migrations and seeder

```sh
php artisan vendor:publish --tag=geonames-migrations
php artisan migrate
php artisan geonames:seed TH US --datasets=countries,admin1,admin2,cities,postal_codes
```

Tables: `geonames_countries`, `geonames_admin1`, `geonames_admin2`, `geonames_cities`,
`geonames_postal_codes`, `geonames_alternate_names` (names are configurable in
`config/geonames.php`; publish it with `--tag=geonames-config`).

- **Keys.** Rows with a `geoname_id` use it as the primary key and are upserted on it, so a
  re-run changes nothing. Postal codes and alternate names have no id of their own, so
  they are replaced per country in one transaction: a code removed from a later snapshot
  goes too. The postal file repeats a few rows byte for byte (JP, CH, IN, FR); each is
  written once and the seeder says how many it skipped. Admin `geonames_code` is an
  index, not a unique key, because an abolished row and its replacement share a code.
- **Abolished rows** are kept, with `status = abolished`.
- `GeonamesSeeder` (publish with `--tag=geonames-seeder`) does the same from `db:seed`.
- Add columns only through new migrations of your own.

### Configuration

| Key | Env | Default |
|---|---|---|
| `geonames.path` | `GEONAMES_PATH` | `storage/app/geonames` |
| `geonames.snapshot` | `GEONAMES_SNAPSHOT` | `null`: the snapshot this release is pinned to. `fetch --snapshot=latest` downloads that snapshot, but reads stay on the pin until you set this |
| `geonames.base_url` | `GEONAMES_BASE_URL` | `https://data.farzai.com/geonames/v1` |

## What is in the data

| Dataset | Rows (snapshot 2026-10-16.1) | Notes |
|---|---|---|
| countries | 252 | bundled; ISO codes, currency, calling code, languages, neighbours |
| admin1 | 3,865 | one global file; keyed by `geoname_id` and `geonames_code` |
| admin2 | 47,641 | per country |
| cities | 236,262 | places with population 500 or more, or an administrative seat; `population_tier` 15000, 5000, 1000 or 500 |
| postal_codes | 4,102,707 in 120 countries | per country; `precision` full or partial |
| alternate_names | 1,478,335 | per country; `lang`, `preferred`, `short`, `colloquial`, `historic` |

Ids are `geoname_id` (plus `wikidata_id` where GeoNames links one). Admin divisions are keyed
by their own `geoname_id`; ISO 3166-2 is a nullable attribute. Time zones are IANA names;
coordinates are WGS84 with five decimals. The schema is
[`data/schema.json`](data/schema.json), and the data rules (null is unknown, ignore unknown
fields and enum values) are those of schema 1. Refreshes are monthly.

## Data licence and attribution

The data is from GeoNames and is licensed under CC BY 4.0. If you show it, say:

> Data from GeoNames (geonames.org), licensed under CC BY 4.0

and note that it was converted (to JSON, NDJSON and SQLite), limited to places with a
population of 500 or more or an administrative seat, with names selected by language,
coordinates rounded to five decimals, and kinds and population tiers added. Postal codes
come from GeoNames' postal dump, which states CC BY 4.0 (its readme links the 3.0 URL; the
snapshot manifest records that). Great Britain's full postal codes carry: "Contains Royal
Mail data © Royal Mail copyright and database right 2022. Source: Office for National
Statistics licensed under the Open Government Licence v.3.0." Every snapshot's
`manifest.json` lists the licence of each source; `data/snapshot.json` repeats the credits
for the pinned one. The data is provided as is, without warranty of accuracy, timeliness
or completeness.

The package code is MIT licensed.

## Testing

```sh
composer test                       # no network
GEONAMES_LIVE=1 vendor/bin/pest --group=live    # fetches Thailand from data.farzai.com
```

## Maintainers

`php tools/update-bundled-data.php latest` moves the pin and the bundled files to the
newest published snapshot; a test checks that the bundled files match the pinned manifest.
