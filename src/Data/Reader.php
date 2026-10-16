<?php

declare(strict_types=1);

namespace Farzai\Geonames\Data;

use Farzai\Geonames\Exceptions\GeonamesException;
use Farzai\Geonames\Exceptions\UnsupportedSchemaException;
use Generator;

/**
 * Reads the two file shapes of a snapshot: a JSON envelope `{schema_version, snapshot,
 * data}` and gzipped NDJSON (one header line, then one row per line). A different
 * schema major is refused; a newer minor, and any field it adds, is read as usual.
 */
final class Reader
{
    public const SUPPORTED_SCHEMA_MAJOR = 1;

    public const MAX_LINE_BYTES = 4 * 1024 * 1024;

    /**
     * @return list<array<string, mixed>>
     */
    public static function envelope(string $path): array
    {
        $json = @file_get_contents($path);
        $decoded = $json === false ? null : json_decode($json, true);
        if (! is_array($decoded) || ! isset($decoded['schema_version']) || ! is_array($decoded['data'] ?? null)) {
            throw new GeonamesException(sprintf('%s is not a snapshot envelope.', $path));
        }
        self::assertSchema((string) $decoded['schema_version']);

        return array_values($decoded['data']);
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    public static function ndjson(string $path): Generator
    {
        $handle = @gzopen($path, 'rb');
        if ($handle === false) {
            throw new GeonamesException(sprintf('Cannot read %s.', $path));
        }

        try {
            $header = self::line($handle, $path);
            $head = $header === false ? null : json_decode($header, true);
            if (! is_array($head) || ! isset($head['schema_version'])) {
                throw new GeonamesException(sprintf('%s has no NDJSON header line.', $path));
            }
            self::assertSchema((string) $head['schema_version']);

            while (($line = self::line($handle, $path)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $row = json_decode($line, true);
                if (is_array($row)) {
                    yield $row;
                }
            }
        } finally {
            gzclose($handle);
        }
    }

    /**
     * One line, at most MAX_LINE_BYTES, so a crafted file cannot inflate a single line without end.
     *
     * @param  resource  $handle
     */
    private static function line($handle, string $path): string|false
    {
        $line = gzgets($handle, self::MAX_LINE_BYTES);
        if ($line !== false && strlen($line) >= self::MAX_LINE_BYTES - 1 && ! str_ends_with($line, "\n")) {
            throw new GeonamesException(sprintf('%s has a line longer than %d bytes.', $path, self::MAX_LINE_BYTES));
        }

        return $line;
    }

    private static function assertSchema(string $version): void
    {
        if ((int) explode('.', $version)[0] !== self::SUPPORTED_SCHEMA_MAJOR) {
            throw UnsupportedSchemaException::major($version, self::SUPPORTED_SCHEMA_MAJOR);
        }
    }
}
