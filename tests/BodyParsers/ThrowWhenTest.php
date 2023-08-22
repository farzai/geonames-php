<?php

use Farzai\Geonames\BodyParsers\ThrowWhen;

it('should parse', function () {
    $parser = new ThrowWhen(function ($body) {
        return strlen($body) > 10;
    }, new \Exception('Body is too long'));

    expect($parser->parse('abc'))->toBe('abc');
    expect($parser->parse('1234567890'))->toBe('1234567890');
});
