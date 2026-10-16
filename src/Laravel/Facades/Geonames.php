<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Facades;

use Farzai\Geonames\GeonamesRepository;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin GeonamesRepository
 *
 * @see GeonamesRepository
 */
final class Geonames extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GeonamesRepository::class;
    }
}
