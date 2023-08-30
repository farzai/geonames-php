<?php

namespace Farzai\Geonames\BodyParsers;

class FromRegex implements BodyParserInterface
{
    public function __construct(
        protected string $regex,
        protected $flags = 0
    ) {
        //
    }

    public function parse($body)
    {
        if (empty($this->regex)) {
            return [];
        }

        if (preg_match_all($this->regex, $body, $matches, $this->flags)) {
            return $this->normalizeItem($matches[1]);
        }

        return [];
    }

    protected function normalizeItem($item)
    {
        return array_filter(array_map(function ($value) {
            return trim($value);
        }, $item));
    }
}
