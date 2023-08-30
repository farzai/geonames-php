<?php

use Farzai\Geonames\BodyParsers\FromRegex;

it('should parse', function () {
    $body = "Line 1\nLine 2\nLine 3";
    $parser = new FromRegex('/^(.*)$/m');

    $expected = ['Line 1', 'Line 2', 'Line 3'];

    expect($parser->parse($body))->toBe($expected);
});

it('should parse with empty body', function () {
    $body = '';
    $parser = new FromRegex('/^(.*)$/');
    $expected = [];

    expect($parser->parse($body))->toBe($expected);
});

it('should parse with empty regex', function () {
    $body = "Line 1\nLine 2\nLine 3";
    $parser = new FromRegex('');
    $expected = [];

    expect($parser->parse($body))->toBe($expected);
});
