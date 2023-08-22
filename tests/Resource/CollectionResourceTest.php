<?php

use Farzai\Geonames\BodyParsers;
use Farzai\Geonames\Resource\CollectionResource;
use Farzai\Geonames\Responses\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;

it('should all returned mapped entities', function () {
    $psrResponse = new GuzzleResponse(200, [], "Line 1\tTitle\tBody\tCreated At");

    $response = new Response($psrResponse);

    $resource = new CollectionResource($response);
    $resource
        ->parseBodyUsing([
            new BodyParsers\FromText(),
        ])
        ->mapEntityUsing(function ($item) {
            return $item[0];
        });

    expect($resource->all())->toBe(['Line 1']);
});
