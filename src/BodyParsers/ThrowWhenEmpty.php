<?php

namespace Farzai\Geonames\BodyParsers;

use Throwable;

class ThrowWhenEmpty extends ThrowWhen
{
    public function __construct(Throwable $exception)
    {
        parent::__construct(function ($body) {
            return empty($body);
        }, $exception);
    }
}
