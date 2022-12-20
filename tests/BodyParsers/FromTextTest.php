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
}
