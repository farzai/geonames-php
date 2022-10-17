<?php

namespace Farzai\Geonames;

use Farzai\Geonames\Resource\CollectionResource;
use Farzai\Geonames\Responses\ResponseInterface;
use Farzai\Geonames\Transports\GuzzleHttpTransport;
use Farzai\Geonames\Transports\TransportInterface;

class Client
{
    /**
     * @var EndpointInterface
     */
    private $endpoint;

    /**
     * Client constructor.
     *
     * @param  TransportInterface|null  $transport
     */
    public function __construct(TransportInterface $transport = null)
    {
        $this->endpoint = new Endpoint($transport ?: new GuzzleHttpTransport());
    }

    /**
     * Get language codes
     *
     * @return \Farzai\Geonames\Resource\CollectionResource
     */
    public function getLanguages(): CollectionResource
    {
        $response = $this->endpoint->getLanguageCodes();

        return $this->createCollectionResource($response)
            ->parseBodyUsing([
                (new BodyParsers\FromText())->startAt(1),
            ])
            ->mapEntityUsing(function ($item) {
                return Entities\LanguageEntity::parse($item);
            });
    }

    /**
     * Get contry info
     *
     * @return CollectionResource
     */
    public function getCountryInfo(): CollectionResource
    {
        $response = $this->endpoint->getCountryInfo();

        return $this->createCollectionResource($response)
            ->parseBodyUsing([
                new BodyParsers\FromText(),
            ])
            ->mapEntityUsing(function ($item) {
                return Entities\CountryEntity::parse($item);
            });
    }

    /**
     * Get geoname countries available
     *
     * @return CollectionResource
     */
    public function getGeonamesAvailable(): CollectionResource
    {
        $response = $this->endpoint->getGeonamesDownloadPage();

        return $this->createCollectionResource($response)
            ->parseBodyUsing([
                new BodyParsers\FromRegex('/<a href="([A-Z]{2})\.zip">([A-Z]{2})\.zip<\/a>/'),
                new BodyParsers\ThrowWhenEmpty(new \RuntimeException('No country codes available')),
            ]);
    }

    /**
     * Get geonames by country code
     *
     * @param  string  $countryCode
     * @return CollectionResource
     */
    public function getGeonamesByCountryCode(string $code): CollectionResource
    {
        $response = $this->endpoint->getGeonamesByCountryCode($code);

        return $this->createCollectionResource($response)
            ->parseBodyUsing([
                new BodyParsers\FromZip(strtoupper($code).'.txt'),
                new BodyParsers\FromText(),
            ])
            ->mapEntityUsing(function ($item) {
                return Entities\GeonameEntity::parse($item);
            });
    }

    /**
     * Create resource from response
     *
     * @param  ResponseInterface  $response
     * @return CollectionResource
     */
    protected function createCollectionResource(ResponseInterface $response): CollectionResource
    {
        return new CollectionResource($response);
    }
}
