<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

use Farzai\Geonames\Exceptions\SnapshotException;

/**
 * The only way this package touches the network, and only inside the fetcher. Tests
 * swap in a fake; the default is {@see StreamTransport}.
 */
interface Transport
{
    public const MAX_SMALL_BYTES = 5 * 1024 * 1024;

    /**
     * The body of a small file (latest.json, snapshots.json, manifest.json), at most
     * `$maxBytes` long.
     *
     * @throws SnapshotException on any non-200 answer, network failure or oversized body
     */
    public function get(string $url, int $maxBytes = self::MAX_SMALL_BYTES): string;

    /**
     * Stream a large file to `$destination`, refusing to write more than `$maxBytes`.
     *
     * @throws SnapshotException on any non-200 answer, network failure or oversized body
     */
    public function download(string $url, string $destination, int $maxBytes): void;
}
