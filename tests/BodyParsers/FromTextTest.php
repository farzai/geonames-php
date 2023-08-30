<?php

use Farzai\Geonames\BodyParsers\FromText;

it('should parse', function () {
    $parser = new FromText();
    $body = "Line 1\tTitle\tBody\tCreated At";
    $expected = [
        ['Line 1', 'Title', 'Body', 'Created At'],
    ];

    expect($parser->parse($body))->toBe($expected);
});

it('should parse with empty body', function () {
    $parser = new FromText();
    $body = '';
    $expected = [];

    expect($parser->parse($body))->toBe($expected);
});

it('should parse with start at', function () {
    $parser = (new FromText())->startAt(1);
    $body = "
        Line 1\tTitle\tBody\tCreated At
        Line 2\tTitle\tBody\tCreated At
    ";
    $expected = [
        ['Line 2', 'Title', 'Body', 'Created At'],
    ];

    expect($parser->parse($body))->toBe($expected);
});

it('should parse with numeric', function () {
    $parser = new FromText();

    $body = "Line 1\tTitle\tBody\t13.00\t14.50\t15.5000\t16";

    $expected = [
        ['Line 1', 'Title', 'Body', 13, 14.50, 15.5, 16],
    ];

    expect($parser->parse($body))->toBe($expected);
});
