<?php

use Farzai\Geonames\Responses\Response;
use GuzzleHttp\Psr7\Response as PsrResponse;

it('should is successful returns true for 2xx status code', function () {
    $response = new Response(new PsrResponse(200));

    expect($response->isSuccessful())->toBeTrue();
});

it('should is successful returns false for non 2xx status code', function () {
    $response = new Response(new PsrResponse(400));

    expect($response->isSuccessful())->toBeFalse();
});

it('should get body returns response body as string', function () {
    $response = new Response(new PsrResponse(200, [], '{"foo": "bar"}'));

    expect($response->getBody())->toBe('{"foo": "bar"}');
});

it('should get psr response returns underlying psr response', function () {
    $psrResponse = new PsrResponse(200);
    $response = new Response($psrResponse);

    expect($response->getPsrResponse())->toBe($psrResponse);
});
