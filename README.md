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


### Get all countries
```php
// GET: https://download.geonames.org/export/dump/countryInfo.txt
$resource = $client->getCountryInfo();

// $entity instanceof \Farzai\Geonames\Entities\CountryEntity
foreach ($resource->all() as $entity) {
    echo $entity->iso;
    // ...
}
```

| Property | Type | Description | Example | Required |
| --- | --- | --- | --- | --- |
| iso | string | ISO 3166-1 alpha-2 | TH | Yes |
| iso3 | string | ISO 3166-1 alpha-3 | THA | Yes |
| iso_numeric | string | ISO 3166-1 numeric | 764 | Yes |
| fips | string | FIPS 10-4 | TH | No |
| name | string | Country name | Thailand | Yes |
| capital | string | Capital | Bangkok | No |
| area | float | Area in km² | 514000.0 | No |
| population | int | Population | 67089500 | No |
| continent | string | Continent code | AS | No |
| tld | string | Top level domain | .th | No |
| currency_code | string | Currency code | THB | No |
| currency_name | string | Currency name | Baht | No |
| phone | string | Phone calling code | 66 | No |
| postal_code_format | string | Postal code format | 5 digits | No |
| postal_code_regex | string | Postal code regex | \^[1-9]\d{4}$ | No |
| languages | string | Languages (comma separated) | th,en,ar-AE,km,la | No |
| geoname_id | string | Geoname ID | 1605651 | No |
| neighbours | string | Neighbours (comma separated) | LA,MM,KH,MY | No |
| equivalent_fips_code | string | Equivalent FIPS code | TH | No |


### Get languages
```php
// GET: https://download.geonames.org/export/dump/iso-languagecodes.txt
$resource = $client->getLanguages();

// $entity instanceof \Farzai\Geonames\Entities\LanguageEntity
foreach ($resource->all() as $entity) {
    // ...
}
```
| Property | Type | Description | Example | Required |
| --- | --- | --- | --- | --- |
| iso_639_3 | string | ISO 639-3 code | tha | Yes |
| iso_639_2 | string | ISO 639-2 code | tha | Yes |
| iso_639_1 | string | ISO 639-1 code | th | Yes |
| language_name | string | Language name | Thai | Yes |



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
| Property | Type | Description | Example | Required |
| --- | --- | --- | --- | --- |
| id | string | Geoname ID | 1605651 | Yes |
| name | string | Name of geographical point (utf8) | Ban Khlong Nung | Yes |
| asciiname | string | Name of geographical point in plain ascii characters | Ban Khlong Nung | Yes |
| alternatenames | string | Alternatenames, comma separated | Ban Khlong Nung,Ban Khlong Nung,Ban Khlong Nung | Yes |
| latitude | float | Latitude in decimal degrees (wgs84) | 13.75 | Yes |
| longitude | float | Longitude in decimal degrees (wgs84) | 100.46667 | Yes |
| feature_class | string | See http://www.geonames.org/export/codes.html | P | Yes |
| feature_code | string | See http://www.geonames.org/export/codes.html | PPL | Yes |
| country_code | string | ISO-3166 2-letter country code, 2 characters | TH | Yes |
| cc2 | string | Alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters | TH | No |
| admin1_code | string | Fipscode (subject to change to iso code), see exceptions below, see file admin1Codes.txt for display names of this code; varchar(20) | 40 | No |
| admin2_code | string | Code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80) | 40 | No |
| admin3_code | string | Code for third level administrative division, varchar(20) | 40 | No |
| admin4_code | string | Code for fourth level administrative division, varchar(20) | 40 | No |
| population | int | Bigint (8 byte int) | 0 | No |
| elevation | int | In meters | 0 | No |
| dem | int | Digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters, integer. srtm processed by cgiar/ciat. | 0 | No |
| timezone | string | The iana timezone id (see file timeZone.txt) varchar(40) | Asia/Bangkok | No |
| modification_date | string | Date of last modification in yyyy-MM-dd format | 2011-03-03 | No |


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

// $entity instanceof \Farzai\Geonames\Entities\AlternateNameEntity
foreach ($resource->all() as $entity) {
    //
}
```
| Property | Type | Description | Example | Required |
| --- | --- | --- | --- | --- |
| id | string | the id of this alternate name | 1 | Yes |
| geoname_id | string | geonameId referring to id in table 'geoname' | 3041563 | Yes |
| iso_language | string | iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata','icao' and faac for airport codes, fr_1793 for French Revolution names,  abbr for abbreviation, link to a website (mostly to wikipedia), wkdt for the wikidataid | en | Yes |
| name | string | alternate name or name variant | Ban Khlong Nung | Yes |
| is_preferred_name | bool | true, if this alternate name is an official/preferred name | true | Yes |
| is_short_name | bool | true, if this is a short name like 'California' for 'State of California' | true | Yes |
| is_colloquial | bool | true, if this alternate name is a colloquial or slang term. Example: 'Big Apple' for 'New York'. | true | Yes |
| is_historic | bool | true, if this alternate name is historic and was used in the past. | true | Yes |
| from | string | from period when the name was used | 2011-03-03 | No |
| to | string | to period when the name was used | 2011-03-03 | No |


## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.