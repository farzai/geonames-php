# Geoname data (PHP)

This package provides a simple way to download Geonames data and format it for friendly use.


## Requirements
```
PHP >= 7.4
```


## Installation
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


### Note
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

// Farzai/Geonames/Entities/CountryEntity[]
$entities = $resource->all();

foreach ($entities as $entity) {
    // Available attributes
    // --------------------
    // $entity->iso
    // $entity->iso3
    // $entity->iso_numeric
    // $entity->fips
    // $entity->country
    // $entity->capital
    // $entity->area
    // $entity->population
    // $entity->continent
    // $entity->tld
    // $entity->currency_code
    // $entity->currency_name
    // $entity->phone
    // $entity->postal_code_format
    // $entity->postal_code_regex
    // $entity->languages
    // $entity->geoname_id
    // $entity->neighbours
    // $entity->equivalent_fips_code
}
```


#### Get languages
```php
// GET: https://download.geonames.org/export/dump/iso-languagecodes.txt
$resource = $client->getLanguages();

// Farzai/Geonames/Entities/LanguageEntity[]
$entities = $resource->all();

foreach ($entities as $entity) {
    // Available attributes
    // --------------------
    // $entity->iso_639_3
    // $entity->iso_639_2
    // $entity->iso_639_1
    // $entity->language_name
}
```

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.