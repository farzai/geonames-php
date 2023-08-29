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

## Note
By default, the package required `guzzlehttp/guzzle` to download the data from Geonames.org.
You must install `GuzzleHttp` before use this package.
```bash
composer require guzzlehttp/guzzle
```

## Example
```php
use Farzai\Geonames\Client;

// Create a new client
$client = new Client();

// Get all countries and return $resource
/** @var \Farzai\Geonames\Resource\CollectionResource $resource */
$resource = $client->getCountryInfo();

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

### Available methods


#### Get all countries
```php
// GET: https://download.geonames.org/export/dump/countryInfo.txt
$resource = $client->getCountryInfo();

/** @var \Farzai\Geonames\Entities\CountryEntity $entity */
foreach ($resource->all() as $entity) {
    $entity->iso // string
    $entity->iso3 // string
    $entity->iso_numeric // string
    $entity->fips  // string
    $entity->name // string
    $entity->capital // string
    $entity->area // float
    $entity->population // int
    $entity->continent // string
    $entity->tld // string
    $entity->currency_code // string
    $entity->currency_name // string
    $entity->phone // string
    $entity->postal_code_format // string
    $entity->postal_code_regex // string
    $entity->languages // string (comma separated: fa,en,ar-AE)
    $entity->geoname_id // string
    $entity->neighbours // string (comma separated: IR,AF,PK,OM,SA,AE,IQ)
    $entity->equivalent_fips_code // string
}
```


#### Get languages
```php
// GET: https://download.geonames.org/export/dump/iso-languagecodes.txt
$resource = $client->getLanguages();

/**
 * @var \Farzai\Geonames\Entities\LanguageEntity $entity
 */
foreach ($resource->all() as $entity) {
    $entity->iso_639_3 // ISO 639-3 code
    $entity->iso_639_2 // ISO 639-2 code
    $entity->iso_639_1 // ISO 639-1 code
    $entity->language_name // Language name
}
```


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

/** @var \Farzai\Geonames\Entities\GeonameEntity $entity */
foreach ($resource->all() as $entity) {
    $entity->id // string
    $entity->name // string
    $entity->asciiname // string
    $entity->alternatenames // string (comma separated)
    $entity->latitude // float
    $entity->longitude // float
    $entity->feature_class // string
    $entity->feature_code // string
    $entity->country_code // string
    $entity->cc2 // string (comma separated)
    $entity->admin1_code // string
    $entity->admin2_code // string
    $entity->admin3_code // string
    $entity->admin4_code // string
    $entity->population // int
    $entity->elevation // int
    $entity->dem // int
    $entity->timezone // string (Example: Asia/Bangkok)
    $entity->modification_date // string (YYYY-MM-DD)
}
```


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



## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.