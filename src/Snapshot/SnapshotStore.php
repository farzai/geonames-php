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
            $home = getenv('HOME');
            $root = is_string($home) && $home !== ''
                ? $home.'/.cache/farzai-geonames'
                : self::privateTempDirectory();
        }

        return new self($root);
    }

    /**
     * With no HOME (php-fpm, cron) the shared temp directory is the only place left,
     * so use a per-user directory there and refuse one that someone else owns.
     */
    private static function privateTempDirectory(): string
    {
        $uid = function_exists('posix_geteuid') ? posix_geteuid() : (int) getmyuid();
        $dir = sys_get_temp_dir().'/farzai-geonames-'.$uid;
        if (! is_dir($dir) && ! @mkdir($dir, 0700) && ! is_dir($dir)) {
            throw new GeonamesException(sprintf('Cannot create %s; set GEONAMES_PATH.', $dir));
        }
        if (is_link($dir) || fileowner($dir) !== $uid || (fileperms($dir) & 0077) !== 0) {
            throw new GeonamesException(sprintf('%s is not private to this user; set GEONAMES_PATH.', $dir));
        }

        return $dir;
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
