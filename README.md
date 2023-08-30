# Geoname data (PHP)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/farzai/geonames.svg?style=flat-square)](https://packagist.org/packages/farzai/geonames-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/farzai/geonames-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/farzai/geonames-php/actions/workflows/run-tests.yml)
[![codecov](https://codecov.io/gh/farzai/geonames-php/branch/main/graph/badge.svg)](https://codecov.io/gh/farzai/geonames-php)
[![Total Downloads](https://img.shields.io/packagist/dt/farzai/geonames.svg?style=flat-square)](https://packagist.org/packages/farzai/geonames)

This package provides a simple way to download Geonames data and format it for friendly use.
(https://www.geonames.org/)

## Requirements
```
PHP ^8.1
ext-curl
ext-json
ext-zip
```

## Setup
You can install the package via Composer:
```bash
composer require farzai/geonames
```

## Example
```php
use Farzai\Geonames\Client;

// Create a new client
$client = new Client();

// Get all countries and return $resource
$resource = $client->getCountryInfo(); // \Farzai\Geonames\Resource\CollectionResource

// Now, you can access the data using the following methods:
$countries = $resource->asArray(); // Array
$json = $resource->asJson(); // String

// Or acccess data as entity
foreach ($resource->all() as $country) {
    /** @var \Farzai\Geonames\Entities\CountryEntity $country */
    echo $country->name;
}
```


---

## Available methods


#### Get all countries
```php
// GET: https://download.geonames.org/export/dump/countryInfo.txt
$resource = $client->getCountryInfo();

// $entity instanceof \Farzai\Geonames\Entities\CountryEntity
foreach ($resource->all() as $entity) {
    echo $entity->iso;
    // ...
}
```

| Description | Property | Type | Example | Required |
| --- | --- | --- | --- | --- |
| ISO 3166-1 alpha-2 | iso | string | TH | Yes |
| ISO 3166-1 alpha-3 | iso3 | string | THA | Yes |
| ISO 3166-1 numeric | iso_numeric | string | 764 | Yes |
| FIPS 10-4 | fips | string | TH | No |
| Country name | name | string | Thailand | Yes |
| Capital | capital | string | Bangkok | No |
| Area in km² | area | float | 514000.0 | No |
| Population | population | int | 67089500 | No |
| Continent code | continent | string | AS | No |
| Top level domain | tld | string | .th | No |
| Currency code | currency_code | string | THB | No |
| Currency name | currency_name | string | Baht | No |
| Phone calling code | phone | string | 66 | No |
| Postal code format | postal_code_format | string | 5 digits | No |
| Postal code regex | postal_code_regex | string | \^[1-9]\d{4}$ | No |
| Languages (comma separated) | languages | string | th,en,ar-AE,km,la | No |
| Geoname ID | geoname_id | string | 1605651 | No |
| Neighbours (comma separated) | neighbours | string | LA,MM,KH,MY | No |
| Equivalent FIPS code | equivalent_fips_code | string | TH | No |


#### Get languages
```php
// GET: https://download.geonames.org/export/dump/iso-languagecodes.txt
$resource = $client->getLanguages();

// $entity instanceof \Farzai\Geonames\Entities\LanguageEntity
foreach ($resource->all() as $entity) {
    // ...
}
```
| Description | Property | Type | Example | Required |
| --- | --- | --- | --- | --- |
| ISO 639-3 code | iso_639_3 | string | tha | Yes |
| ISO 639-2 code | iso_639_2 | string | tha | Yes |
| ISO 639-1 code | iso_639_1 | string | th | Yes |
| Language name | language_name | string | Thai | Yes |



### Geonames available resources
```php
// GET: https://download.geonames.org/export/dump
$resource = $client->getGeonamesAvailable();

/** @var string[] $countryCodes */
$countryCodes = $resource->all();
```

### Geonames by country code
```php
// GET: https://download.geonames.org/export/dump/{countryCode}.zip
$resource = $client->getGeonamesByCountryCode('TH');

// $entity instanceof \Farzai\Geonames\Entities\GeonameEntity
foreach ($resource->all() as $entity) {
    // ...
}
```
| Description | Property | Type | Example | Required |
| --- | --- | --- | --- | --- |
| geonameid | id | string | 1605651 | Yes |
| name of geographical point (utf8) | name | string | Ban Khlong Nung | Yes |
| name of geographical point in plain ascii characters | asciiname | string | Ban Khlong Nung | Yes |
| alternatenames, comma separated | alternatenames | string | Ban Khlong Nung,Ban Khlong Nung,Ban Khlong Nung | Yes |
| latitude in decimal degrees (wgs84) | latitude | float | 13.75 | Yes |
| longitude in decimal degrees (wgs84) | longitude | float | 100.46667 | Yes |
| see http://www.geonames.org/export/codes.html | feature_class | string | P | Yes |
| see http://www.geonames.org/export/codes.html | feature_code | string | PPL | Yes |
| ISO-3166 2-letter country code, 2 characters | country_code | string | TH | Yes |
| alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters | cc2 | string | TH | No |
| fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20) | admin1_code | string | 40 | No |
| code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80) | admin2_code | string | 40 | No |
| code for third level administrative division, varchar(20) | admin3_code | string | 40 | No |
| code for fourth level administrative division, varchar(20) | admin4_code | string | 40 | No |
| bigint (8 byte int) | population | int | 0 | No |
| in meters | elevation | int | 0 | No |
| digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat. | dem | int | 0 | No |
| the iana timezone id (see file timeZone.txt) varchar(40) | timezone | string | Asia/Bangkok | No |
| date of last modification in yyyy-MM-dd format | modification_date | string | 2011-03-03 | No |


### Alternate names available resources
```php
// GET: https://download.geonames.org/export/dump/alternatenames
$resource = $client->getAlternateNamesAvailable();

/** @var string[] $countryCodes */
$countryCodes = $resource->all();
```

### Alternate names by country code
```php
// GET: https://download.geonames.org/export/dump/alternatenames/{countryCode}.zip
$resource = $client->getAlternateNamesByCountryCode('TH');

/** @var \Farzai\Geonames\Entities\AlternateNameEntity $entity */
foreach ($resource->all() as $entity) {
    $entity->id // string
    $entity->geoname_id // string
    $entity->iso_language // string
    $entity->name // string (Alternate name)
    $entity->is_preferred_name // bool
    $entity->is_short_name // bool
    $entity->is_colloquial // bool
    $entity->is_historic // bool
    $entity->from // string (YYYY-MM-DD)
    $entity->to // string (YYYY-MM-DD)
}
```
| Description | Property | Type | Example | Required |
| --- | --- | --- | --- | --- |
| the id of this alternate name, int | id | string | 1 | Yes |
| geonameId referring to id in table 'geoname', int | geoname_id | string | 3041563 | Yes |
| iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link to a website (mostly to wikipedia), wkdt for the wikidataid, varchar(7) | iso_language | string | en | Yes |
| alternate name or name variant, varchar(400) | name | string | Ban Khlong Nung | Yes |
| true, if this alternate name is an official/preferred name | is_preferred_name | bool | true | Yes |
| true, if this is a short name like 'California' for 'State of California' | is_short_name | bool | true | Yes |
| true, if this alternate name is a colloquial or slang term. Example: 'Big Apple' for 'New York'. | is_colloquial | bool | true | Yes |
| true, if this alternate name is historic and was used in the past. | is_historic | bool | true | Yes |
| from period when the name was used | from | string | 2011-03-03 | No |
| to period when the name was used | to | string | 2011-03-03 | No |


## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.