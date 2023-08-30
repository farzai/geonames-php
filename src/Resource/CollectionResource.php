<?php

namespace Farzai\Geonames\Resource;

use Farzai\Geonames\Entities\EntityInterface;
use Farzai\Transport\Contracts\ResponseInterface;

class CollectionResource implements ResourceInterface
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var callable
     */
    protected $mapEntityCallback;

    /**
     * Caching the result
     *
     * @var array|null
     */
    protected $items;

    /**
     * @var \Farzai\Geonames\BodyParsers\BodyParserInterface[]
     */
    protected $pipelineParsers;

    /**
     * Resource constructor.
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;

        $this->mapEntityCallback = function ($item) {
            return $item;
        };
    }

    public function parseBodyUsing(array $parsers)
    {
        $this->pipelineParsers = $parsers;

        return $this;
    }

    /**
     * Get response
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Return entites
     */
    public function all(): array
    {
        if ($this->items === null) {
            $this->items = array_map(function ($item) use (&$index) {
                return call_user_func($this->mapEntityCallback, $item, $index++);
            }, $this->parseBody());
        }

        return $this->items;
    }

    /**
     * Return response as array
     */
    public function asArray(): array
    {
        return array_map(function ($item) {
            if ($item instanceof EntityInterface) {
                $item = $item->toArray();
            }

            return $item;
        }, $this->all());
    }

    /**
     * Return response as json
     */
    public function asJson(): string
    {
        return json_encode($this);
    }

    public function mapEntityUsing(callable $callback): ResourceInterface
    {
        $this->mapEntityCallback = $callback;

        return $this;
    }

    private function parseBody()
    {
        $body = $this->response->getBody();

        foreach ($this->pipelineParsers as $parser) {
            $body = $parser->parse($body);
        }

        return $body;
    }

    /**
     * To string
     */
    public function __toString(): string
    {
        return $this->response->getBody();
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
