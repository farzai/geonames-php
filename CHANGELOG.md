# Changelog

All notable changes to `geonames-php` will be documented in this file.

## 3.0.0 - 2026-10-16

Rewrite around the verified Farzai GeoNames snapshots. Read UPGRADING.md before you upgrade from 2.x.

- Reads the snapshots published at https://data.farzai.com/geonames/ (pinned snapshot, sha256-verified, no network at runtime); `geonames fetch` and `php artisan geonames:fetch` download what you ask for.
- New API: `Geonames::country()`, `admin1()`, `admin2()`, `cities()`, `city()`, `postalCodes()`, `search()`, `nearest()` and read-only models.
- Laravel: validation rules, Eloquent casts, publishable migrations, seeder (tested on SQLite, Postgres 16, MySQL 8.4, MariaDB 10.2 and 11.8), config.
- Removed: the downloaders, all converters including MongoDB, the `--feature-class` option, `farzai/transport`, `symfony/http-client`, `symfony/console`, `psr/log`, `ext-zip`.
- PHP 8.3 or newer.

Data: GeoNames, CC BY 4.0. See the README for attribution.

PRs: #52, #53, #54, #55, #56, #57. Closes #40, #51.

## Unreleased

### 3.0.0: rewrite around verified Farzai GeoNames snapshots

- Reads the snapshots published at https://data.farzai.com/geonames/ (pinned snapshot, sha256-verified, no network at runtime); `geonames fetch` and `php artisan geonames:fetch` download what you ask for.
- New API: `Geonames::country()`, `admin1()`, `admin2()`, `cities()`, `city()`, `postalCodes()`, `search()`, `nearest()` and read-only models.
- Laravel: validation rules, Eloquent casts, publishable migrations, seeder, config.
- Removed: the downloaders, all converters including MongoDB, the `--feature-class` option, `farzai/transport`, `symfony/http-client`, `symfony/console`, `psr/log`, `ext-zip`.
- PHP 8.3 or newer. See UPGRADING.md.

## 2.1.0 - 2025-02-27

### What's Changed

* Feature: MongoDB Output Implementation by @parsilver in https://github.com/farzai/geonames-php/pull/17

**Full Changelog**: https://github.com/farzai/geonames-php/compare/2.0.0...2.1.0

## 2.0.0 - 2025-02-13

### What's Changed

* Change test library to Pest and setup to standard flow. by @parsilver in https://github.com/farzai/geonames-php/pull/4
* No longer to supported < 8.1 by @parsilver in https://github.com/farzai/geonames-php/pull/5
* Refactor and modernize Geonames library by @parsilver in https://github.com/farzai/geonames-php/pull/16
* Bump codecov/codecov-action from 4 to 5 by @dependabot in https://github.com/farzai/geonames-php/pull/13

### New Contributors

* @dependabot made their first contribution in https://github.com/farzai/geonames-php/pull/6

**Full Changelog**: https://github.com/farzai/geonames-php/compare/1.0.3...2.0.0
