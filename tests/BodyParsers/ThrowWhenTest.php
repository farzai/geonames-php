<?php

namespace Farzai\Geonames\Tests\BodyParsers;

use Farzai\Geonames\BodyParsers\ThrowWhen;
use PHPUnit\Framework\TestCase;

class ThrowWhenTest extends TestCase
{
    public function testParse()
    {
        $parser = new ThrowWhen(function ($body) {
            return strlen($body) > 10;
        }, new \Exception('Body is too long'));

        $this->assertEquals('abc', $parser->parse('abc'));
        $this->assertEquals('1234567890', $parser->parse('1234567890'));
    }

    public function testParseThrowsException()
    {
        $parser = new ThrowWhen(function ($body) {
            return strlen($body) > 10;
        }, new \Exception('Body is too long'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Body is too long');

        $parser->parse('12345678901');
    }
}
