<?php

namespace Farzai\Geonames\Resource;

use Farzai\Geonames\Entities\EntityInterface;
use Farzai\Geonames\Responses\ResponseInterface;

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
    protected $bodyParsers;

    /**
     * Resource constructor.
     *
     * @param  ResponseInterface  $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;

        $this->mapEntityCallback = function ($item) {
            return $item;
        };
    }

    public function parseBodyUsing(array $bodyParsers)
    {
        $this->bodyParsers = $bodyParsers;

        return $this;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Return entites
     *
     * @return array
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
     *
     * @return array
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
     *
     * @return string
     */
    public function asJson(): string
    {
        return json_encode($this);
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->response->getBody();
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }

    /**
     * @param  callable  $callback
     * @return ResourceInterface
     */
    public function mapEntityUsing(callable $callback): ResourceInterface
    {
        $this->mapEntityCallback = $callback;

        return $this;
    }

    private function parseBody()
    {
        $body = $this->response->getBody();

        foreach ($this->bodyParsers as $bodyParser) {
            $body = $bodyParser->parse($body);
        }

        return $body;
    }
}
