#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Refreshes the data bundled with the package (countries, kinds, time_zones and the
 * snapshot's schema.json) and the pin (data/snapshot.json) from a published snapshot.
 * Run by a maintainer when a release moves to a newer snapshot; nothing here runs for
 * a user of the package.
 *
 *   php tools/update-bundled-data.php            # re-verify the files of the current pin
 *   php tools/update-bundled-data.php latest     # move the pin to latest.json's snapshot
 *   php tools/update-bundled-data.php 2026-11-01.1
 */

use Farzai\Geonames\Exceptions\IntegrityException;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotFetcher;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Snapshot\StreamTransport;

require __DIR__.'/../vendor/autoload.php';

$baseUrl = 'https://data.farzai.com/geonames/v1';
$dataDir = dirname(__DIR__).'/data';
$wanted = $argv[1] ?? null;

$pinFile = $dataDir.'/snapshot.json';
$pin = is_file($pinFile) ? Pin::bundled($pinFile) : new Pin('', '', '');
$transport = new StreamTransport;
$store = new SnapshotStore(sys_get_temp_dir().'/geonames-bundle-'.bin2hex(random_bytes(4)));
$fetcher = new SnapshotFetcher($transport, $store, $pin, $baseUrl);

$snapshot = $wanted ?? ($pin->snapshot !== '' ? $pin->snapshot : 'latest');
$result = $fetcher->fetch([], ['countries', 'kinds', 'time_zones'], false, $snapshot);
$manifest = $store->manifest($result->snapshot) ?? throw new RuntimeException('manifest was not stored');

$schemaEntry = $manifest->file('schema.json') ?? throw new RuntimeException('the snapshot has no schema.json');
$schema = $transport->get($baseUrl.'/'.$result->snapshot.'/schema.json');
if (hash('sha256', $schema) !== $schemaEntry['sha256']) {
    throw IntegrityException::checksum('schema.json', $schemaEntry['sha256'], hash('sha256', $schema));
}

if (! is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}
$hashes = [];
foreach (['countries.json', 'kinds.json', 'time_zones.json'] as $file) {
    copy($store->path($result->snapshot, $file), $dataDir.'/'.$file);
    $hashes[$file] = $manifest->file($file)['sha256'];
}
file_put_contents($dataDir.'/schema.json', $schema);
$hashes['schema.json'] = $schemaEntry['sha256'];

$resolved = $fetcher->resolve($result->snapshot);
file_put_contents($pinFile, json_encode([
    'snapshot' => $result->snapshot,
    'manifest_sha256' => $resolved['manifest_sha256'],
    'schema_version' => $manifest->schemaVersion,
    'files' => $hashes,
    'credits' => $manifest->credits(),
    'attribution' => 'Data from GeoNames (geonames.org), licensed under CC BY 4.0',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

fwrite(STDOUT, sprintf("Bundled snapshot %s (manifest %s)\n", $result->snapshot, $resolved['manifest_sha256']));
