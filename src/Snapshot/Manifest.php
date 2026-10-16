<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * manifest.json of a snapshot: every file with its sha256 and size, plus the counts,
 * sources and licences that go with the data. Unknown fields are ignored.
 */
final class Manifest
{
    /**
     * @param  array<string, array{sha256: string, bytes: int}>  $files
     * @param  array<string, mixed>  $raw
     */
    private function __construct(
        public readonly string $snapshot,
        public readonly string $schemaVersion,
        public readonly array $files,
        public readonly array $raw,
    ) {}

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (! is_array($data) || ! isset($data['snapshot'], $data['schema_version'], $data['files']) || ! is_array($data['files'])) {
            throw new GeonamesException('manifest.json is not a snapshot manifest.');
        }

        $files = [];
        foreach ($data['files'] as $path => $entry) {
            if (is_array($entry) && isset($entry['sha256'], $entry['bytes']) && is_string($entry['sha256'])) {
                $files[(string) $path] = ['sha256' => $entry['sha256'], 'bytes' => (int) $entry['bytes']];
            }
        }

        return new self((string) $data['snapshot'], (string) $data['schema_version'], $files, $data);
    }

    public function has(string $path): bool
    {
        return isset($this->files[$path]);
    }

    /**
     * @return array{sha256: string, bytes: int}|null
     */
    public function file(string $path): ?array
    {
        return $this->files[$path] ?? null;
    }

    /**
     * The credits a user of this snapshot must show (for example the GB Royal Mail credit).
     *
     * @return list<string>
     */
    public function credits(): array
    {
        return array_values(array_filter((array) ($this->raw['credits'] ?? []), 'is_string'));
    }
}
