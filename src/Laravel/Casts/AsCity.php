<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Casts;

use Farzai\Geonames\Exceptions\NotFoundException;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Models\City;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the city's `geoname_id`; reads back the {@see City} from the countries that
 * were fetched. Same rules as AsCountry for unknown and blank values.
 *
 * @implements CastsAttributes<City|null, City|int|string|null>
 */
final class AsCity implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?City
    {
        return is_numeric($value) ? app(GeonamesRepository::class)->city((int) $value) : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof City) {
            return $value->geonameId();
        }
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            throw new \InvalidArgumentException('A city cast takes a City, a geoname id or null.');
        }
        $city = app(GeonamesRepository::class)->city((int) $value);

        return ($city ?? throw NotFoundException::for('city', $value))->geonameId();
    }
}
