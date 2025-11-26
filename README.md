# Geoname data (PHP)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/farzai/geonames.svg?style=flat-square)](https://packagist.org/packages/farzai/geonames-php)
[![Tests](https://img.shields.io/github/actions/workflow/status/farzai/geonames-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/farzai/geonames-php/actions/workflows/run-tests.yml)
[![codecov](https://codecov.io/gh/farzai/geonames-php/branch/main/graph/badge.svg)](https://codecov.io/gh/farzai/geonames-php)
[![Total Downloads](https://img.shields.io/packagist/dt/farzai/geonames.svg?style=flat-square)](https://packagist.org/packages/farzai/geonames)
A PHP library for downloading and converting Geonames data. This library provides easy access to both Postal Codes and Gazetteer data from Geonames.

## Features

- Download postal codes data for specific countries or all countries
- Download detailed geographical data (Gazetteer) including administrative divisions
- Convert data to JSON format with proper structure
- Import data directly to MongoDB with proper indexing
- Memory-efficient processing for large datasets
- Progress bars for all operations

## How It Works

### Admin Code Resolution
When downloading gazetteer data, the library automatically downloads `admin1CodesASCII.txt` and `admin2Codes.txt` from GeoNames to resolve administrative division names (e.g., converting admin code "40" to "Bangkok").

### Memory-Efficient Processing
Data is processed using streaming to handle large datasets without exhausting memory. For MongoDB imports, records are inserted in batches of 1000 for optimal performance.

### Automatic Cleanup
Temporary files (downloaded ZIP files, extracted data files, and admin code files) are automatically cleaned up after processing completes.

## Installation

```bash
composer require farzai/geonames
```

## Usage

### Postal Codes

Download postal codes for a specific country:

```bash
./bin/geonames geonames:download TH
```

Download postal codes for all countries:

```bash
./bin/geonames geonames:download all
```

Options:
- `--output (-o)`: Output directory (default: ./data)
- `--format (-f)`: Output format (default: json, options: json, mongodb)
- `--mongodb-uri`: MongoDB connection URI (default: mongodb://localhost:27017)
- `--mongodb-db`: MongoDB database name (default: geonames)
- `--mongodb-collection`: MongoDB collection name (default: postal_codes)

The postal codes data includes:
- Country code
- Postal code
- Place name
- Administrative divisions (state/province, county/district, community)
- Latitude and longitude
- Accuracy level

### Gazetteer Data

Download geographical data for a specific country:

```bash
./bin/geonames geonames:gazetteer:download TH
```

Download geographical data for all countries:

```bash
./bin/geonames geonames:gazetteer:download all
```

Options:
- `--output (-o)`: Output directory (default: ./data)
- `--format (-f)`: Output format (default: json, options: json, mongodb)
- `--mongodb-uri`: MongoDB connection URI (default: mongodb://localhost:27017)
- `--mongodb-db`: MongoDB database name (default: geonames)
- `--mongodb-collection`: MongoDB collection name (default: gazetteer)

The Gazetteer data includes:
- Geoname ID
- Name (with ASCII and alternate names)
- Geographical coordinates
- Feature class and code
- Administrative divisions with names
- Population
- Elevation
- Digital elevation model (DEM)
- Timezone
- Modification date

## Data Structure

### Postal Codes Structure

#### JSON Format

```json
{
    "country_code": "TH",
    "postal_code": "10200",
    "place_name": "Bang Rak",
    "admin_name1": "Bangkok",
    "admin_code1": "10",
    "admin_name2": "",
    "admin_code2": "",
    "admin_name3": "",
    "admin_code3": "",
    "latitude": 13.7235,
    "longitude": 100.5147,
    "accuracy": 1
}
```

#### MongoDB Format

In MongoDB, the postal codes data has the same structure as JSON but includes an additional `location` field for geospatial queries:

```json
{
    "country_code": "TH",
    "postal_code": "10200",
    "place_name": "Bang Rak",
    "admin_name1": "Bangkok",
    "admin_code1": "10",
    "admin_name2": "",
    "admin_code2": "",
    "admin_name3": "",
    "admin_code3": "",
    "latitude": 13.7235,
    "longitude": 100.5147,
    "accuracy": 1,
    "location": {
        "type": "Point",
        "coordinates": [100.5147, 13.7235]
    }
}
```

The MongoDB collection is indexed for efficient queries:
- Compound index on `country_code` and `postal_code` (unique)
- Index on `country_code`
- Index on `postal_code`
- Geospatial index on `location`

### Gazetteer Structure

#### JSON Format

```json
{
    "geoname_id": 1609350,
    "name": "Bangkok",
    "ascii_name": "Bangkok",
    "alternate_names": ["Krung Thep", "กรุงเทพมหานคร"],
    "latitude": 13.75,
    "longitude": 100.51667,
    "feature_class": "P",
    "feature_code": "PPLC",
    "country_code": "TH",
    "cc2": [],
    "admin1_code": "40",
    "admin1_name": "Bangkok",
    "admin2_code": "",
    "admin2_name": "",
    "admin3_code": "",
    "admin4_code": "",
    "population": 5104476,
    "elevation": 2,
    "dem": 4,
    "timezone": "Asia/Bangkok",
    "modification_date": "2023-01-12"
}
```

#### MongoDB Format

In MongoDB, the gazetteer data uses slightly different field names and includes a `location` field for geospatial queries:

```json
{
    "geonameid": 1609350,
    "name": "Bangkok",
    "asciiname": "Bangkok",
    "alternatenames": ["Krung Thep", "กรุงเทพมหานคร"],
    "location": {
        "type": "Point",
        "coordinates": [100.51667, 13.75]
    },
    "feature_class": "P",
    "feature_code": "PPLC",
    "country_code": "TH",
    "cc2": [],
    "admin1_code": "40",
    "admin1_name": "Bangkok",
    "admin2_code": "",
    "admin2_name": "",
    "admin3_code": "",
    "admin4_code": "",
    "population": 5104476,
    "elevation": 2,
    "dem": 4,
    "timezone": "Asia/Bangkok",
    "modification_date": "2023-01-12"
}
```

The MongoDB collection is indexed for efficient queries:
- Geospatial index on `location`

## MongoDB Usage Examples

### Finding locations near a point

```php
$client = new MongoDB\Client('mongodb://localhost:27017');
$collection = $client->geonames->gazetteer;

// Find all places within 5km of Bangkok
$result = $collection->find([
    'location' => [
        '$near' => [
            '$geometry' => [
                'type' => 'Point',
                'coordinates' => [100.51667, 13.75] // [longitude, latitude]
            ],
            '$maxDistance' => 5000 // 5km in meters
        ]
    ]
]);

foreach ($result as $place) {
    echo $place['name'] . ' - ' . $place['feature_code'] . PHP_EOL;
}
```

### Finding postal codes by country

```php
$client = new MongoDB\Client('mongodb://localhost:27017');
$collection = $client->geonames->postal_codes;

// Find all postal codes in Bangkok, Thailand
$result = $collection->find([
    'country_code' => 'TH',
    'admin_name1' => 'Bangkok'
]);

foreach ($result as $postalCode) {
    echo $postalCode['postal_code'] . ' - ' . $postalCode['place_name'] . PHP_EOL;
}
```

## Error Handling

The library throws `Farzai\Geonames\Exceptions\GeonamesException` for all error conditions:

```php
use Farzai\Geonames\Exceptions\GeonamesException;

try {
    // Download or convert operations
} catch (GeonamesException $e) {
    echo "Error: " . $e->getMessage();
}
```

Common error scenarios:
- **File operation failures**: Unable to read, write, or open files
- **ZIP extraction failures**: Corrupted or invalid ZIP archives
- **Data not found**: Missing expected data files in downloaded archives
- **Missing dependencies**: MongoDB extension not installed when using MongoDB format

## License

This package is open-sourced software licensed under the MIT license.

## Credits

- Data provided by [GeoNames](https://www.geonames.org/) under a [Creative Commons Attribution 4.0 License](https://creativecommons.org/licenses/by/4.0/)
- Developed by [Parsilver](https://github.com/parsilver)