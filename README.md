# Geoname data (PHP)

This package provides a simple way to download Geonames data and format it for friendly use.
(https://www.geonames.org/)

## Requirements
```
PHP >= 7.4
ext-curl
ext-json
ext-zip
```

## Setup via Composer
```bash
composer require farzai/geonames
```

## Usage

```php
use Farzai\Geonames\Client;
use Farzai\Geonames\Responses\ResponseInterface;

$client = new Client();

// Example: Get all countries
$resource = $geonames->getCountryInfo();

// After getting the resource, you can access the data using the following methods:
$countries = $resource->asArray(); // Array
$json = $resource->asJson(); // String
```


### Customizing the client
By default, the package has a dependency on `guzzlehttp/guzzle` to download the data. If you want to use a different HTTP client, you can install the package without the default dependency and use your own HTTP client.

 
After that, You must create new transport class and implement `Farzai\Geonames\Transports\TransportInterface`.

```php
use Farzai\Geonames\Transports\TransportInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class MySymfonyTransport implements TransportInterface
{
    /**
     * Send request to geonames
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest(string $method, string $url, array $options = []): ResponseInterface
    {
        // Send request and return the response
        // ...

        return $response;
    }
}
```

Then, you can pass the transport class to the client:

```php
use Farzai\Geonames\Client;
use App\Transports\MySymfonyTransport;

$client = new Client(new MySymfonyTransport());
// ...
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
    $entity->country // string
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