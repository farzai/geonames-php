<?php

declare(strict_types=1);

namespace Farzai\Geonames\Tests\Helpers;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A mock PSR-18 HTTP client for testing.
 */
class MockHttpClient implements ClientInterface
{
    /**
     * @var array<ResponseInterface>
     */
    private array $responses;

    private int $currentIndex = 0;

    /**
     * @param  array<ResponseInterface>  $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->currentIndex >= count($this->responses)) {
            throw new \RuntimeException('No more mock responses available');
        }

        return $this->responses[$this->currentIndex++];
    }

    /**
     * Create mock responses from content.
     *
     * @param  array<array{content: string, headers?: array<string, array<string>>}>  $responseData
     */
    public static function withResponses(array $responseData): self
    {
        $responses = [];
        foreach ($responseData as $data) {
            $content = $data['content'];
            $headers = $data['headers'] ?? ['Content-Length' => [strlen($content)]];
            $responses[] = new MockResponse($content, $headers);
        }

        return new self($responses);
    }

    /**
     * Create a Transport instance with mock responses for testing.
     *
     * @param  array<array{content: string, headers?: array<string, array<string>>}>  $responses
     */
    public static function createTransport(array $responses): \Farzai\Transport\Transport
    {
        $mockClient = self::withResponses($responses);

        return \Farzai\Transport\TransportBuilder::make()
            ->setClient($mockClient)
            ->build();
    }
}

/**
 * A mock PSR-7 response.
 */
class MockResponse implements ResponseInterface
{
    private string $body;

    /**
     * @var array<string, array<string>>
     */
    private array $headers;

    private MockStream $stream;

    /**
     * @param  array<string, array<string>>  $headers
     */
    public function __construct(string $body, array $headers = [])
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->stream = new MockStream($body);
    }

    public function getStatusCode(): int
    {
        return 200;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this;
    }

    public function getReasonPhrase(): string
    {
        return 'OK';
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        return $this;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * @return array<string>
     */
    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @param  string|array<string>  $value
     */
    public function withHeader(string $name, $value): ResponseInterface
    {
        return $this;
    }

    /**
     * @param  string|array<string>  $value
     */
    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        return $this;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        return $this;
    }
}

/**
 * A mock PSR-7 stream.
 */
class MockStream implements StreamInterface
{
    private string $content;

    private int $position = 0;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void {}

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $this->position = $offset;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        return 0;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read(int $length): string
    {
        $data = substr($this->content, $this->position, $length);
        $this->position += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        $remaining = substr($this->content, $this->position);
        $this->position = strlen($this->content);

        return $remaining;
    }

    /**
     * @return array<mixed>|null
     */
    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }
}
