<?php

use Farzai\Geonames\BodyParsers;
use Farzai\Geonames\Resource\CollectionResource;
use Farzai\Transport\Response;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;

it('should all returned mapped entities', function () {
    $request = $this->createMock(PsrRequestInterface::class);
    $psrResponse = new PsrResponse(200, [], "Line 1\tTitle\tBody\tCreated At");

    $resource = new CollectionResource(new Response($request, $psrResponse));
    $resource
        ->parseBodyUsing([
            new BodyParsers\FromText(),
        ])
        ->mapEntityUsing(function ($item) {
            return $item[0];
        });

    expect($resource->all())->toBe(['Line 1']);
});
