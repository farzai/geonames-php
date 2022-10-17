<?php

namespace Farzai\Geonames\BodyParsers;

use Throwable;

class ThrowWhen implements BodyParserInterface
{
    protected $exception;

    protected $condition;

    public function __construct(callable $condition, Throwable $exception)
    {
        $this->condition = $condition;
        $this->exception = $exception;
    }

    public function parse($body)
    {
        if (call_user_func($this->condition, $body)) {
            throw $this->exception;
        }

        return $body;
    }
}
