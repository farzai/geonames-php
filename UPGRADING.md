# Upgrading to 3.0

3.0 is a rewrite around the verified snapshots Farzai publishes at
<https://data.farzai.com/geonames/>. It no longer downloads from geonames.org or converts
files; a pipeline does that once a month, and this package reads the result.

## Removed

| 2.x | 3.0 |
|---|---|
| `GeonamesDownloader`, `GazetteerDownloader`, `AbstractDownloader` | `geonames fetch` (checked against a manifest) |
| `PostalCodeConverter`, `GazetteerConverter`, `AbstractConverter`, `ConverterInterface` | `Geonames::...` reads the snapshot files |
| `MongoDBPostalCodeConverter`, `MongoDBGazetteerConverter`, `mongodb/mongodb` | none; use the Laravel migrations and seeder, or the files |
| `geonames:download`, `geonames:gazetteer` commands | `geonames fetch` and `php artisan geonames:fetch` |
| `--feature-class`, `-c` | `kind` (a small enum) plus `feature_class` and `feature_code` on every row |
| `--format`, `--mongodb-*`, `--output` | none |
| `GeonamesException` for everything | `GeonamesException` is the base of the specific exceptions below |
| `farzai/transport`, `symfony/http-client`, `symfony/console`, `psr/log`, `ext-zip` | not needed |

## Requirements

PHP 8.3 or newer (was 8.2) with `ext-json` and `ext-zlib`; `illuminate/collections` and
`illuminate/contracts` 12 or 13.

## Calls

```php
// 2.x
$converter->convert($zipFile, $outputFile, $adminDir);   // postal or gazetteer JSON

// 3.0
// once:   vendor/bin/geonames fetch TH --datasets=admin1,admin2,cities,postal_codes
Geonames::cities('TH');                // Collection<City>
Geonames::postalCodes('GB', 'SW1A 1AA'); // Collection<PostalCode>
```

## What changed in the data

- Rows are `geoname_id`-keyed and carry `wikidata_id` where GeoNames links one.
- `names` is `{bcp47: name}`; the old flat `name`/`asciiname`/`alternatenames` strings are gone.
  An alternate-names file per country has `lang`, `preferred`, `short`, `colloquial` and
  `historic`.
- The cities dataset is places with a population of 500 or more or an administrative seat,
  not every GeoNames feature. The full gazetteer is not published.
- Postal rows have `precision` (`full` or `partial`) and a `source`. One postal code serves
  many places: there is no unique `(country, postal_code)`.
- Thai postal codes are not in the snapshot; use `farzai/thailand-address`.
- Admin divisions are keyed by `geoname_id` and `geonames_code` (`TH.40`). ISO 3166-2 is
  nullable and never a key.
- Coordinates are WGS84 with five decimals; time zones are IANA names.

## Exceptions

| Exception | When |
|---|---|
| `NotFoundException` | an `*OrFail` lookup, or a cast set with an unknown code |
| `NotFetchedException` | the data is not in the local store; the message names the command |
| `PostalCodesElsewhereException` | `postalCodes('TH')` |
| `IntegrityException` | a downloaded file does not match the manifest |
| `SnapshotException` | a withdrawn or unknown snapshot, a failed download, a bad country code or dataset |
| `UnsupportedSchemaException` | a different schema major version |

All extend `Farzai\Geonames\Exceptions\GeonamesException`.

## Laravel

New in 3.0: validation rules (`geonames_country`, `geonames_admin1`, `geonames_city`,
`geonames_postal_code`), casts (`AsCountry`, `AsAdmin1`, `AsCity`), publishable migrations
and a seeder, `geonames:fetch` and `geonames:seed`, and `config/geonames.php`. Nothing runs
until you publish and migrate.

## Silent 2.x pitfalls that no longer exist

- The 2.1 call `GazetteerConverter::convert($zip, $out, $adminDir)` silently lost admin names
  once the third argument was dropped. There is no converter now.
- The gazetteer MongoDB import could double every record on a re-run, and the postal import
  rejected about 41% of rows on its unique index. The seeder upserts on `geoname_id` where
  there is one and replaces postal codes and alternate names per country.
