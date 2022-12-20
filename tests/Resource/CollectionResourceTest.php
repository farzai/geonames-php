<?php

namespace Farzai\Geonames\Tests\Resource;

use Farzai\Geonames\Tests\TestCase;
use Farzai\Geonames\Responses\Response;
use Farzai\Geonames\Resource\CollectionResource;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Farzai\Geonames\BodyParsers;

class CollectionResourceTest extends TestCase
{

    public function testAllReturnsMappedEntities()
    {
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

        $this->assertEquals(['Line 1'], $resource->all());
    }
}
