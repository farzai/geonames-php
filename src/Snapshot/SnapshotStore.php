<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

use Farzai\Geonames\Exceptions\GeonamesException;

/**
 * The directory the verified files live in: `<root>/<snapshot>/<manifest path>`.
 * Only the fetcher writes here, and only after a file passed its sha256 check.
 */
final class SnapshotStore
{
    public function __construct(private readonly string $root) {}

    public static function default(): self
    {
        $root = getenv('GEONAMES_PATH');
        if (! is_string($root) || $root === '') {
            $home = getenv('HOME') ?: sys_get_temp_dir();
            $root = $home.'/.cache/farzai-geonames';
        }

        return new self($root);
    }

    public function root(): string
    {
        return $this->root;
    }

    public function snapshotDir(string $snapshot): string
    {
        if (preg_match('/^[0-9A-Za-z][0-9A-Za-z._-]*$/', $snapshot) !== 1) {
            throw new GeonamesException(sprintf('"%s" is not a valid snapshot id.', $snapshot));
        }

        return $this->root.'/'.$snapshot;
    }

    public function path(string $snapshot, string $relative): string
    {
        Datasets::assertSafePath($relative);

        return $this->snapshotDir($snapshot).'/'.$relative;
    }

    public function has(string $snapshot, string $relative): bool
    {
        return is_file($this->path($snapshot, $relative));
    }

    public function manifest(string $snapshot): ?Manifest
    {
        $path = $this->snapshotDir($snapshot).'/manifest.json';

        return is_file($path) ? Manifest::fromJson((string) file_get_contents($path)) : null;
    }
}
