<?php

namespace Farzai\Geonames\BodyParsers;

class FromRegex implements BodyParserInterface
{
    protected $regex;

    public function __construct($regex)
    {
        $this->regex = $regex;
    }

    public function parse($body)
    {
        preg_match_all($this->regex, $body, $matches);

        return $this->normalizeItem($matches[1]);
    }

    protected function normalizeItem($item)
    {
        return array_map(function ($value) {
            return trim($value);
        }, $item);
    }
}
