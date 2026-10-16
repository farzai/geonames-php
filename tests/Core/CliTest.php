<?php

declare(strict_types=1);

use Farzai\Geonames\Console\Cli;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Tests\Support\FakeTransport;
use Farzai\Geonames\Tests\Support\FixtureSnapshot;

/**
 * @param  list<string>  $argv
 * @return array{int, string, string, SnapshotStore}
 */
function runCli(object $test, array $argv): array
{
    $out = fopen('php://memory', 'w+');
    $err = fopen('php://memory', 'w+');
    $store = new SnapshotStore($test->temporaryDirectory());
    $fixture = new FixtureSnapshot;
    $cli = new Cli(new FakeTransport($fixture->routes()), 'https://data.test/geonames/v1', $fixture->pin());
    $code = $cli->run($argv, $out, $err, $store);
    rewind($out);
    rewind($err);

    return [$code, (string) stream_get_contents($out), (string) stream_get_contents($err), $store];
}

it('prints help', function () {
    [$code, $out] = runCli($this, ['help']);

    expect($code)->toBe(0)->and($out)->toContain('geonames fetch')->and($out)->toContain('CC BY 4.0');
});

it('rejects an unknown command and an unknown option with exit code 1', function () {
    [$code, , $err] = runCli($this, ['download']);
    expect($code)->toBe(1)->and($err)->toContain('Unknown command');

    [$code, , $err] = runCli($this, ['fetch', 'TH', '--feature-class=P']);
    expect($code)->toBe(1)->and($err)->toContain('Unknown option');
});

it('refuses a country code that is not two letters', function () {
    [$code, , $err] = runCli($this, ['fetch', '../../x']);

    expect($code)->toBe(1)->and($err)->toContain('alpha-2');
});

it('fetches what is asked for and says what was downloaded and what the snapshot lacks', function () {
    [$code, $out, , $store] = runCli($this, ['fetch', 'TH', 'US', '--datasets=cities,postal_codes']);

    expect($code)->toBe(0)
        ->and($out)->toContain('downloaded  by-country/US/cities.ndjson.gz')
        ->and($out)->toContain('not in this snapshot  by-country/TH/postal_codes.ndjson.gz (Thai postal codes: use farzai/thailand-address)')
        ->and($out)->toContain('3 downloaded, 0 already there, 1 not in the snapshot.')
        ->and($store->has(FixtureSnapshot::SNAPSHOT, 'by-country/TH/cities.ndjson.gz'))->toBeTrue();

    [, $again] = runCli($this, ['fetch', 'TH', '--datasets=cities', '--path='.$store->root()]);
    expect($again)->toContain('up to date  by-country/TH/cities.ndjson.gz');
});

it('fetches only the SQLite file when asked for just --sqlite', function () {
    [$code, $out, , $store] = runCli($this, ['fetch', 'TH', '--sqlite']);

    expect($code)->toBe(0)
        ->and($out)->toContain('downloaded  sqlite/TH.sqlite')
        ->and($store->has(FixtureSnapshot::SNAPSHOT, 'by-country/TH/cities.ndjson.gz'))->toBeFalse();
});

it('reports a snapshot it cannot verify without writing anything', function () {
    $out = fopen('php://memory', 'w+');
    $err = fopen('php://memory', 'w+');
    $store = new SnapshotStore($this->temporaryDirectory());
    $fixture = new FixtureSnapshot(status: 'withdrawn');

    $code = (new Cli(new FakeTransport($fixture->routes()), 'https://data.test/geonames/v1', $fixture->pin()))->run(['fetch', 'TH'], $out, $err, $store);
    rewind($err);

    expect($code)->toBe(1)->and((string) stream_get_contents($err))->toContain('withdrawn')->and(glob($store->root().'/*'))->toBe([]);
});
