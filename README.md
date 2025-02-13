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
- Memory-efficient processing for large datasets
- Progress bars for all operations
- Support for filtering by feature types (for Gazetteer data)

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
- `--format (-f)`: Output format (default: json)

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
- `--format (-f)`: Output format (default: json)
- `--feature-class (-c)`: Filter by feature class (default: P)

Available feature classes:
- `A`: Country, state, region
- `H`: Stream, lake
- `L`: Parks, area
- `P`: City, village
- `R`: Road, railroad
- `S`: Spot, building, farm
- `T`: Mountain, hill, rock
- `U`: Undersea
- `V`: Forest, heath

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

### Postal Codes JSON Structure

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

### Gazetteer JSON Structure

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

## License

This package is open-sourced software licensed under the MIT license.

## Credits

- Data provided by [GeoNames](https://www.geonames.org/) under a [Creative Commons Attribution 4.0 License](https://creativecommons.org/licenses/by/4.0/)
- Developed by [Parsilver](https://github.com/parsilver)