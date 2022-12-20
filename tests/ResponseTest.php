<?php

namespace Farzai\Geonames\Tests;

use Farzai\Geonames\Responses\Response;
use GuzzleHttp\Psr7\Response as PsrResponse;

class ResponseTest extends TestCase
{
    public function testIsSuccessfulReturnsTrueFor2xxStatusCode()
    {
        $response = new Response(new PsrResponse(200));

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsSuccessfulReturnsFalseForNon2xxStatusCode()
    {
        $response = new Response(new PsrResponse(400));

        $this->assertFalse($response->isSuccessful());
    }

    public function testGetBodyReturnsResponseBodyAsString()
    {
        $response = new Response(new PsrResponse(200, [], '{"foo": "bar"}'));

        $this->assertEquals('{"foo": "bar"}', $response->getBody());
    }

    public function testGetPsrResponseReturnsUnderlyingPsrResponse()
    {
        $psrResponse = new PsrResponse(200);
        $response = new Response($psrResponse);

        $this->assertSame($psrResponse, $response->getPsrResponse());
    }
}
