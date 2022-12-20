<?php

namespace Farzai\Geonames\Tests\BodyParsers;

use Farzai\Geonames\BodyParsers\FromZip;
use PHPUnit\Framework\TestCase;

class FromZipTest extends TestCase
{
    public function testParse()
    {
        $parser = new FromZip('text.txt');
        $expected = "Test contents\n";

        // Create zip file
        $zip = new \ZipArchive();
        $zip->open('test.zip', \ZipArchive::CREATE);
        $zip->addFromString('text.txt', $expected);
        $zip->close();

        $zipFile = file_get_contents('test.zip');

        $this->assertEquals($expected, $parser->parse($zipFile));
    }
}
