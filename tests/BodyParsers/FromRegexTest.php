<?php

namespace Farzai\Geonames\Tests\BodyParsers;

use Farzai\Geonames\Tests\TestCase;
use Farzai\Geonames\BodyParsers\FromRegex;

class FromRegexTest extends TestCase
{
    public function testParse()
    {
        $parser = new FromRegex('/^(.*)$/m');
        $body = "Line 1\nLine 2\nLine 3";
        $expected = ['Line 1', 'Line 2', 'Line 3'];

        $this->assertEquals($expected, $parser->parse($body));
    }

    public function testParseWithEmptyBody()
    {
        $parser = new FromRegex('/^(.*)$/m');
        $body = '';
        $expected = [];

        $this->assertEquals($expected, $parser->parse($body));
    }

    public function testParseWithEmptyRegex()
    {
        $parser = new FromRegex('');
        $body = "Line 1\nLine 2\nLine 3";
        $expected = [];

        $this->assertEquals($expected, $parser->parse($body));
    }
}
