#!/usr/bin/env bash
# Installs this package into an empty project the way a user does (no dev dependencies),
# runs the CLI from vendor/bin, and reads the bundled data. With --live it also fetches
# Thailand from data.farzai.com, verified against the manifest.
set -euo pipefail

repo="$(cd "$(dirname "$0")/../.." && pwd)"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
cd "$work"

composer init --no-interaction --name=smoke/app --stability=dev >/dev/null
composer config repositories.local path "$repo"
composer config minimum-stability dev
composer require farzai/geonames:@dev --update-no-dev --no-interaction >/dev/null

vendor/bin/geonames help | grep -q "geonames fetch"
guard="$(vendor/bin/geonames fetch ../../x 2>&1 || true)"
echo "$guard" | grep -q "alpha-2" || { echo "country code guard missing" >&2; exit 1; }

php -r 'require "vendor/autoload.php";
  $th = Farzai\Geonames\Geonames::country("TH");
  if ($th === null || $th->nameTh !== "ไทย") { fwrite(STDERR, "bundled countries unreadable\n"); exit(1); }'

if [ "${1:-}" = "--live" ]; then
  GEONAMES_PATH="$work/store" vendor/bin/geonames fetch TH --datasets=cities,admin1 | tee fetch.log
  grep -q "downloaded  by-country/TH/cities.ndjson.gz" fetch.log
  GEONAMES_PATH="$work/store" php -r 'require "vendor/autoload.php";
    $n = Farzai\Geonames\Geonames::cities("TH")->count();
    if ($n < 1000) { fwrite(STDERR, "too few Thai cities: $n\n"); exit(1); }'
fi
echo "consumer smoke ok"
