<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

use Farzai\Geonames\Exceptions\GeonamesException;
use Farzai\Geonames\Exceptions\IntegrityException;
use Farzai\Geonames\Exceptions\SnapshotException;
use Farzai\Geonames\Exceptions\UnsupportedSchemaException;

/**
 * Downloads files of a snapshot into the store, and trusts nothing it did not verify:
 * the manifest against the pinned (or `latest.json`) sha256, every file against the
 * manifest's sha256 and size, written through a `.part` file and renamed only when
 * both match.
 */
final class SnapshotFetcher
{
    public const SUPPORTED_SCHEMA_MAJOR = 1;

    public function __construct(
        private readonly Transport $transport,
        private readonly SnapshotStore $store,
        private readonly Pin $pin,
        private readonly string $baseUrl = 'https://data.farzai.com/geonames/v1',
    ) {}

    /**
     * The snapshot to read: the pin, or what `latest.json` names when `$snapshot` is
     * 'latest'. It must be listed in snapshots.json as published, and its manifest
     * hash must agree with the pin or with latest.json.
     *
     * @return array{snapshot: string, manifest_sha256: string}
     */
    public function resolve(?string $snapshot = null): array
    {
        if ($snapshot === 'latest') {
            $latest = $this->json($this->baseUrl.'/latest.json');
            $this->assertSchema((string) ($latest['schema_version'] ?? ''));
            $id = (string) ($latest['snapshot'] ?? '');
            $sha = (string) ($latest['manifest_sha256'] ?? '');
        } else {
            $id = $snapshot ?? $this->pin->snapshot;
            $sha = $id === $this->pin->snapshot ? $this->pin->manifestSha256 : '';
        }
        if ($id === '') {
            throw new GeonamesException('latest.json does not name a snapshot.');
        }

        $listed = $this->json($this->baseUrl.'/snapshots.json');
        $entry = null;
        foreach ((array) ($listed['snapshots'] ?? []) as $candidate) {
            if (is_array($candidate) && ($candidate['id'] ?? null) === $id) {
                $entry = $candidate;
            }
        }
        if ($entry === null) {
            throw SnapshotException::unknown($id);
        }
        if (($entry['status'] ?? null) !== 'published') {
            throw SnapshotException::withdrawn($id, (string) ($entry['status'] ?? 'not published'));
        }
        $listedSha = (string) ($entry['manifest_sha256'] ?? '');
        if ($sha === '') {
            $sha = $listedSha;
        } elseif ($listedSha !== '' && $listedSha !== $sha) {
            throw new IntegrityException(sprintf('snapshots.json lists a different manifest hash for %s than expected.', $id));
        }
        if ($sha === '') {
            throw new IntegrityException(sprintf('No manifest hash is known for snapshot %s, so it cannot be verified.', $id));
        }

        return ['snapshot' => $id, 'manifest_sha256' => $sha];
    }

    /**
     * @param  list<string>  $countries  ISO 3166-1 alpha-2 codes
     * @param  list<string>  $datasets  names from {@see Datasets}
     */
    public function fetch(array $countries = [], array $datasets = Datasets::DEFAULT, bool $sqlite = false, ?string $snapshot = null): FetchResult
    {
        $countries = array_values(array_unique(array_map(Datasets::country(...), $countries)));
        foreach ($datasets as $dataset) {
            Datasets::path($dataset, 'XX'); // throws for a name that is not a dataset
        }

        ['snapshot' => $id, 'manifest_sha256' => $sha] = $this->resolve($snapshot);
        $manifest = $this->manifest($id, $sha);

        $wanted = [];
        foreach ($datasets as $dataset) {
            if (Datasets::isGlobal($dataset)) {
                $wanted[] = Datasets::path($dataset);

                continue;
            }
            foreach ($countries as $country) {
                $wanted[] = Datasets::path($dataset, $country);
            }
        }
        if ($sqlite) {
            foreach ($countries as $country) {
                $wanted[] = Datasets::sqlitePath($country);
            }
        }

        $downloaded = $skipped = $missing = [];
        foreach (array_unique($wanted) as $path) {
            $entry = $manifest->file($path);
            if ($entry === null) {
                $missing[] = $path;

                continue;
            }
            Datasets::assertSafePath($path);
            $destination = $this->store->path($id, $path);
            if (is_file($destination) && $this->matches($destination, $entry)) {
                $skipped[] = $path;

                continue;
            }
            $this->download($this->baseUrl.'/'.$id.'/'.$path, $destination, $entry);
            $downloaded[] = $path;
        }

        return new FetchResult($id, $downloaded, $skipped, $missing);
    }

    private function manifest(string $snapshot, string $expectedSha): Manifest
    {
        $dir = $this->store->snapshotDir($snapshot);
        $path = $dir.'/manifest.json';
        if (is_file($path) && hash_file('sha256', $path) === $expectedSha) {
            return Manifest::fromJson((string) file_get_contents($path));
        }

        $body = $this->transport->get($this->baseUrl.'/'.$snapshot.'/manifest.json');
        $actual = hash('sha256', $body);
        if (! hash_equals($expectedSha, $actual)) {
            throw IntegrityException::checksum('manifest.json', $expectedSha, $actual);
        }
        $manifest = Manifest::fromJson($body);
        $this->assertSchema($manifest->schemaVersion);
        if ($manifest->snapshot !== $snapshot) {
            throw new IntegrityException(sprintf('manifest.json is for snapshot %s, not %s.', $manifest->snapshot, $snapshot));
        }

        $this->ensureDirectory($dir);
        $this->atomicWrite($path, $body);

        return $manifest;
    }

    /**
     * @param  array{sha256: string, bytes: int}  $entry
     */
    private function download(string $url, string $destination, array $entry): void
    {
        $this->ensureDirectory(dirname($destination));
        $part = $destination.'.part';

        try {
            $this->transport->download($url, $part, $entry['bytes']);
            clearstatcache(true, $part);
            $size = (int) filesize($part);
            if ($size !== $entry['bytes']) {
                throw IntegrityException::size(basename($destination), $entry['bytes'], $size);
            }
            $actual = (string) hash_file('sha256', $part);
            if (! hash_equals($entry['sha256'], $actual)) {
                throw IntegrityException::checksum(basename($destination), $entry['sha256'], $actual);
            }
            if (! rename($part, $destination)) {
                throw new GeonamesException(sprintf('Could not move %s into place.', $destination));
            }
        } finally {
            if (is_file($part)) {
                @unlink($part);
            }
        }
    }

    /**
     * @param  array{sha256: string, bytes: int}  $entry
     */
    private function matches(string $file, array $entry): bool
    {
        return filesize($file) === $entry['bytes'] && hash_file('sha256', $file) === $entry['sha256'];
    }

    /**
     * @return array<string, mixed>
     */
    private function json(string $url): array
    {
        $data = json_decode($this->transport->get($url), true);
        if (! is_array($data)) {
            throw SnapshotException::download($url, 'the answer is not JSON');
        }

        return $data;
    }

    private function assertSchema(string $version): void
    {
        if ((int) explode('.', $version)[0] !== self::SUPPORTED_SCHEMA_MAJOR) {
            throw UnsupportedSchemaException::major($version, self::SUPPORTED_SCHEMA_MAJOR);
        }
    }

    private function ensureDirectory(string $dir): void
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new GeonamesException(sprintf('Cannot create %s.', $dir));
        }
    }

    private function atomicWrite(string $path, string $body): void
    {
        $part = $path.'.part';
        if (file_put_contents($part, $body) === false || ! rename($part, $path)) {
            @unlink($part);
            throw new GeonamesException(sprintf('Cannot write %s.', $path));
        }
    }
}
