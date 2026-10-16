<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * The snapshot this release of the package reads, with the sha256 of its manifest.
 * It never follows `latest.json` by itself; only a release moves it.
 */
final class Pin
{
    public function __construct(
        public readonly string $snapshot,
        public readonly string $manifestSha256,
        public readonly string $schemaVersion,
    ) {}

    public static function bundled(?string $file = null): self
    {
        $file ??= dirname(__DIR__, 2).'/data/snapshot.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        if (! is_array($data) || ! isset($data['snapshot'], $data['manifest_sha256'], $data['schema_version'])) {
            throw new GeonamesException(sprintf('%s is not a snapshot pin.', $file));
        }

        return new self((string) $data['snapshot'], (string) $data['manifest_sha256'], (string) $data['schema_version']);
    }
}
