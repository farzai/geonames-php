<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

/**
 * What a fetch did: files downloaded, files already in the store and verified, and
 * files the snapshot does not have (for example Thai postal codes).
 */
final class FetchResult
{
    /**
     * @param  list<string>  $downloaded
     * @param  list<string>  $skipped
     * @param  list<string>  $missing
     */
    public function __construct(
        public readonly string $snapshot,
        public readonly array $downloaded = [],
        public readonly array $skipped = [],
        public readonly array $missing = [],
    ) {}
}
