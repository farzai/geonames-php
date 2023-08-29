<?php

namespace Farzai\Geonames;

use Farzai\Geonames\Resource\CollectionResource;
use Farzai\Transport\Contracts\ResponseInterface;
use Farzai\Transport\TransportBuilder;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;

class Client
{
    /**
     * @var EndpointInterface
     */
    private $endpoint;

    /**
     * Client constructor.
     */
    public function __construct(
        private ?PsrClientInterface $client = null,
        private ?PsrLoggerInterface $logger = null,
    ) {
        $transport = TransportBuilder::make();
        if ($this->client) {
            $transport->setClient($this->client);
        }

        if ($this->logger) {
            $transport->setLogger($this->logger);
        }

        $this->endpoint = new Endpoint($transport->build());
    }

    /**
     * Get language codes
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
     * Get available of alternate names
     */
    public function getAlternateNamesAvailable(): CollectionResource
    {
        $response = $this->endpoint->getAlternateNamesDownloadPage();

        return $this->createCollectionResource($response)
            ->parseBodyUsing([
                new BodyParsers\FromRegex('/<a href="([A-Z]{2})\.zip">([A-Z]{2})\.zip<\/a>/'),
                new BodyParsers\ThrowWhenEmpty(new \RuntimeException('No country codes available')),
            ]);
    }

    /**
     * Get alternate names by country code
     *
     * @param  string  $countryCode
     */
    public function getAlternateNamesByCountryCode(string $code): CollectionResource
    {
        $response = $this->endpoint->getAlternateNamesByCountryCode($code);

        return $this->createCollectionResource($response)
            ->parseBodyUsing([
                new BodyParsers\FromZip(strtoupper($code).'.txt'),
                new BodyParsers\FromText(),
            ])
            ->mapEntityUsing(function ($item) {
                return Entities\AlternateNameEntity::parse($item);
            });
    }

    /**
     * Create resource from response
     */
    protected function createCollectionResource(ResponseInterface $response): CollectionResource
    {
        return new CollectionResource($response);
    }
}
