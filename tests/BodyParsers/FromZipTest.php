<?php

use Farzai\Geonames\BodyParsers\FromZip;

it('should parse', function () {
    $parser = new FromZip('text.txt');
    $expected = "Test contents\n";

    // Create zip file
    $zip = new \ZipArchive();
    $zip->open('test.zip', \ZipArchive::CREATE);
    $zip->addFromString('text.txt', $expected);
    $zip->close();

    $zipFile = file_get_contents('test.zip');

    expect($parser->parse($zipFile))->toBe($expected);
});
