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
    /**
     * The body of a small file (latest.json, snapshots.json, manifest.json).
     *
     * @throws SnapshotException on any non-200 answer or network failure
     */
    public function get(string $url): string;

    /**
     * Stream a large file to `$destination`.
     *
     * @throws SnapshotException on any non-200 answer or network failure
     */
    public function download(string $url, string $destination): void;
}
