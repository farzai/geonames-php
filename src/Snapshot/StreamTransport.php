<?php

declare(strict_types=1);

namespace Farzai\Geonames\Snapshot;

use Farzai\Geonames\Exceptions\SnapshotException;

/**
 * PHP streams over HTTPS, so the package needs no HTTP client. Redirects are
 * followed, every answer but 200 is an error, and a short read is an error.
 */
final class StreamTransport implements Transport
{
    public function __construct(
        private readonly int $timeoutSeconds = 60,
        private readonly string $userAgent = 'farzai-geonames/3.0 (+https://github.com/farzai/geonames-php)',
    ) {}

    public function get(string $url): string
    {
        $handle = $this->open($url);
        try {
            $body = stream_get_contents($handle);
        } finally {
            fclose($handle);
        }
        if ($body === false) {
            throw SnapshotException::download($url, 'the answer could not be read');
        }

        return $body;
    }

    public function download(string $url, string $destination): void
    {
        $source = $this->open($url);
        $target = @fopen($destination, 'wb');
        if ($target === false) {
            fclose($source);
            throw SnapshotException::download($url, sprintf('cannot write %s', $destination));
        }

        try {
            if (stream_copy_to_stream($source, $target) === false) {
                throw SnapshotException::download($url, 'the transfer was cut short');
            }
        } finally {
            fclose($source);
            fclose($target);
        }
    }

    /**
     * @return resource
     */
    private function open(string $url)
    {
        if (! str_starts_with($url, 'https://') && ! str_starts_with($url, 'http://')) {
            throw SnapshotException::download($url, 'only http and https URLs are fetched');
        }

        $context = stream_context_create(['http' => [
            'method' => 'GET',
            'header' => "User-Agent: {$this->userAgent}\r\nAccept: */*\r\n",
            'timeout' => $this->timeoutSeconds,
            'follow_location' => 1,
            'max_redirects' => 3,
            'ignore_errors' => true,
        ]]);

        $handle = @fopen($url, 'rb', false, $context);
        if ($handle === false) {
            $error = error_get_last();
            throw SnapshotException::download($url, $error['message'] ?? 'the connection failed');
        }

        // $http_response_header is set in this scope by the http wrapper.
        $headers = function_exists('stream_get_meta_data') ? (stream_get_meta_data($handle)['wrapper_data'] ?? []) : [];
        $status = 0;
        foreach ((array) $headers as $line) {
            if (is_string($line) && preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) {
                $status = (int) $m[1]; // the last status line wins after redirects
            }
        }
        if ($status !== 200) {
            fclose($handle);
            throw SnapshotException::download($url, sprintf('HTTP %d', $status));
        }

        return $handle;
    }
}
