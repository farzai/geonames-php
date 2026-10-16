<?php

declare(strict_types=1);

namespace Farzai\Geonames\Console;

use Farzai\Geonames\Exceptions\GeonamesException;
use Farzai\Geonames\Snapshot\Datasets;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotFetcher;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Snapshot\StreamTransport;
use Farzai\Geonames\Snapshot\Transport;

/**
 * `bin/geonames`, with no dependency: only `fetch` talks to the network, and everything
 * it writes is checked against the manifest first.
 */
final class Cli
{
    public const BASE_URL = 'https://data.farzai.com/geonames/v1';

    public function __construct(
        private readonly ?Transport $transport = null,
        private readonly string $baseUrl = self::BASE_URL,
        private readonly ?Pin $pin = null,
    ) {}

    /**
     * @param  list<string>  $argv  without the script name
     * @param  resource  $out
     * @param  resource  $err
     */
    public function run(array $argv, $out = null, $err = null, ?SnapshotStore $store = null): int
    {
        $out ??= STDOUT;
        $err ??= STDERR;
        $command = $argv[0] ?? 'help';

        try {
            return match ($command) {
                'fetch' => $this->fetch(array_slice($argv, 1), $out, $store ?? SnapshotStore::default()),
                'help', '--help', '-h' => $this->help($out),
                default => $this->fail($err, sprintf('Unknown command "%s". Try: geonames help', $command)),
            };
        } catch (GeonamesException $e) {
            return $this->fail($err, $e->getMessage());
        }
    }

    /**
     * @param  list<string>  $args
     * @param  resource  $out
     */
    private function fetch(array $args, $out, SnapshotStore $store): int
    {
        $countries = [];
        $datasets = null;
        $sqlite = false;
        $snapshot = null;
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--datasets=')) {
                $value = substr($arg, strlen('--datasets='));
                $datasets = $value === 'all' ? ['admin1', ...Datasets::PER_COUNTRY] : array_values(array_filter(explode(',', $value)));
            } elseif ($arg === '--sqlite') {
                $sqlite = true;
            } elseif (str_starts_with($arg, '--snapshot=')) {
                $snapshot = substr($arg, strlen('--snapshot='));
            } elseif (str_starts_with($arg, '--path=')) {
                $store = new SnapshotStore(substr($arg, strlen('--path=')));
            } elseif (str_starts_with($arg, '-')) {
                throw new GeonamesException(sprintf('Unknown option "%s". Try: geonames help', $arg));
            } else {
                $countries[] = $arg;
            }
        }

        // With only --sqlite and no dataset named, fetch just the SQLite files.
        $datasets ??= $sqlite && $countries !== [] ? [] : Datasets::DEFAULT;
        $fetcher = new SnapshotFetcher($this->transport ?? new StreamTransport, $store, $this->pin ?? Pin::bundled(), $this->baseUrl);
        $result = $fetcher->fetch($countries, $datasets, $sqlite, $snapshot);

        fwrite($out, sprintf("Snapshot %s, store %s\n", $result->snapshot, $store->root()));
        foreach ($result->downloaded as $path) {
            fwrite($out, "  downloaded  $path\n");
        }
        foreach ($result->skipped as $path) {
            fwrite($out, "  up to date  $path\n");
        }
        foreach ($result->missing as $path) {
            fwrite($out, "  not in this snapshot  $path".(str_contains($path, '/TH/postal_codes') ? ' (Thai postal codes: use farzai/thailand-address)' : '')."\n");
        }
        fwrite($out, sprintf("%d downloaded, %d already there, %d not in the snapshot.\n", count($result->downloaded), count($result->skipped), count($result->missing)));

        return 0;
    }

    /**
     * @param  resource  $out
     */
    private function help(mixed $out): int
    {
        fwrite($out, <<<'TXT'
geonames: GeoNames data from verified Farzai snapshots (https://data.farzai.com/geonames/)

  geonames fetch [COUNTRY ...] [options]

Options
  --datasets=a,b   admin1, admin2, cities, alternate_names, postal_codes, or "all"
                   (default: admin1,admin2,cities)
  --sqlite         also fetch each country's SQLite file (nearest-city lookups)
  --snapshot=ID    a snapshot id, or "latest" (default: the one this release is pinned to)
  --path=DIR       store directory (default: $GEONAMES_PATH or ~/.cache/farzai-geonames)

Every file is checked against the manifest's sha256 and size before it is kept.
Data from GeoNames (geonames.org), licensed under CC BY 4.0.

TXT);

        return 0;
    }

    /**
     * @param  resource  $err
     */
    private function fail(mixed $err, string $message): int
    {
        fwrite($err, 'geonames: '.$message."\n");

        return 1;
    }
}
