<?php

use Farzai\Geonames\BodyParsers\FromRegex;

it('should parse', function () {
    $parser = new FromRegex('/^(.*)$/m');
    $body = "Line 1\nLine 2\nLine 3";
    $expected = ['Line 1', 'Line 2', 'Line 3'];

    expect($parser->parse($body))->toBe($expected);
});

it('should parse with empty body', function () {
    $parser = new FromRegex('/^(.*)$/m');
    $body = '';
    $expected = [];

    expect($parser->parse($body))->toBe($expected);
});

it('should parse with empty regex', function () {
    $parser = new FromRegex('');
    $body = "Line 1\nLine 2\nLine 3";
    $expected = [];

    expect($parser->parse($body))->toBe($expected);
});
