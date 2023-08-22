<?php

namespace Farzai\Geonames\Tests\BodyParsers;

use Farzai\Geonames\Tests\TestCase;
use Farzai\Geonames\BodyParsers\FromText;

class FromTextTest extends TestCase
{
    public function testParse()
    {
        $parser = new FromText();
        $body = "Line 1\tTitle\tBody\tCreated At";
        $expected = [['Line 1', 'Title', 'Body', 'Created At']];

        $this->assertEquals($expected, $parser->parse($body));
    }

    public function testParseWithEmptyBody()
    {
        $parser = new FromText();
        $body = '';
        $expected = [];

        $this->assertEquals($expected, $parser->parse($body));
    }

    public function testParseWithStartAt()
    {
        $parser = (new FromText())->startAt(1);
        $body = "
            Line 1\tTitle\tBody\tCreated At
            Line 2\tTitle\tBody\tCreated At
        ";
        $expected = [
            ['Line 2', 'Title', 'Body', 'Created At'],
        ];

        $this->assertEquals($expected, $parser->parse($body));
    }

    public function testParseWithNumeric()
    {
        $parser = new FromText();

        $body = "Line 1\tTitle\tBody\t13.00\t14.50\t15.5000\t16";
        
        $expected = [
            ['Line 1', 'Title', 'Body', 13, 14.50, 15.5, 16],
        ];

        $this->assertEquals($expected, $parser->parse($body));
    }
}
