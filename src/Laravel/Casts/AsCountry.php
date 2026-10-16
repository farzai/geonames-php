<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Casts;

use Farzai\Geonames\Exceptions\NotFoundException;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Models\Country;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the ISO alpha-2 code; reads back the {@see Country}. Setting an unknown code
 * throws {@see NotFoundException}, as Laravel's enum casts do. Reading one back gives
 * null: a row must not fail because a code stopped existing. A blank value clears it.
 *
 * @implements CastsAttributes<Country|null, Country|string|int|null>
 */
final class AsCountry implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Country
    {
        return is_string($value) && $value !== '' ? app(GeonamesRepository::class)->country($value) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Country) {
            return $value->iso2();
        }
        if (! is_string($value) && ! is_int($value)) {
            throw new \InvalidArgumentException('A country cast takes a Country, a country code or null.');
        }

        return app(GeonamesRepository::class)->countryOrFail($value)->iso2();
    }
}
