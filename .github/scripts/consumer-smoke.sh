#!/usr/bin/env bash
# Install this package the way a user does, into an empty project with
# --no-dev and no plugins allowed, then use it: the CLI must run from
# vendor/bin and a downloader must build its HTTP client (#34, #35).
#
# The package is taken from `git archive`, so .gitattributes export-ignore
# applies as it does to the Packagist dist, and uncommitted changes are
# included through `git stash create` (which does not touch the stash list).
#
# Usage: consumer-smoke.sh [--download]
#   --download  also download and convert one small country (LI) over the
#               network.
set -euo pipefail

repo=$(cd "$(dirname "$0")/../.." && pwd)
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT

ref=$(git -C "$repo" stash create)
mkdir "$work/package" "$work/app"
git -C "$repo" archive --format=tar "${ref:-HEAD}" | tar -x -C "$work/package"

cd "$work/app"
cat > composer.json <<JSON
{
    "repositories": [{"type": "path", "url": "$work/package", "options": {"symlink": false, "versions": {"farzai/geonames": "3.0.0"}}}],
    "minimum-stability": "stable"
}
JSON
composer require --update-no-dev --no-interaction --no-progress --quiet farzai/geonames:3.0.0

failed=0
check() {
    local name=$1; shift
    echo "--- $name"
    if "$@"; then echo "PASS: $name"; else echo "FAIL: $name"; failed=1; fi
}

check "CLI runs from vendor/bin" bash -c 'set -o pipefail; vendor/bin/geonames list --no-ansi | head -n 12'
check "a downloader builds its HTTP client" \
    php -r 'require "vendor/autoload.php"; new Farzai\Geonames\Downloader\GeonamesDownloader(); echo "downloader built\n";'

if [ "${1:-}" = "--download" ]; then
    check "download and convert LI" sh -c "vendor/bin/geonames geonames:download LI --output '$work/out' --no-ansi && ls -l '$work/out'"
fi

exit $failed
