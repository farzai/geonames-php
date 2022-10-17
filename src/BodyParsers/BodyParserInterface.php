<?php

namespace Farzai\Geonames\BodyParsers;

interface BodyParserInterface
{
    /**
     * Parse the body
     *
     * @param  mixed  $body
     * @return mixed
     */
    public function parse($body);
}
