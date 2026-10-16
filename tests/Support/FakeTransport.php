<?php

declare(strict_types=1);

namespace Farzai\Geonames\Tests\Support;

use Farzai\Geonames\Exceptions\SnapshotException;
use Farzai\Geonames\Snapshot\Transport;

final class FakeTransport implements Transport
{
    /** @var list<string> */
    public array $requests = [];

    /**
     * @param  array<string, string>  $routes  url path (after the base URL) => body
     */
    public function __construct(public array $routes, private readonly string $base = 'https://data.test/geonames/v1/') {}

    public function get(string $url): string
    {
        return $this->body($url);
    }

    public function download(string $url, string $destination): void
    {
        file_put_contents($destination, $this->body($url));
    }

    public function cut(string $path, int $keep): void
    {
        $this->routes[$path] = substr($this->routes[$path], 0, $keep);
    }

    public function replace(string $path, string $body): void
    {
        $this->routes[$path] = $body;
    }

    private function body(string $url): string
    {
        $this->requests[] = $url;
        $path = str_starts_with($url, $this->base) ? substr($url, strlen($this->base)) : $url;
        if (! array_key_exists($path, $this->routes)) {
            throw SnapshotException::download($url, 'HTTP 404');
        }

        return $this->routes[$path];
    }
}
