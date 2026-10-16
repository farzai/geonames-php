<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Casts;

use Farzai\Geonames\Exceptions\NotFoundException;
use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Models\Admin1;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores the GeoNames code (`TH.50`, which is unique across countries); reads back the
 * {@see Admin1}. Needs the admin1 data to have been fetched. Same rules as AsCountry for
 * unknown and blank values.
 *
 * @implements CastsAttributes<Admin1|null, Admin1|string|null>
 */
final class AsAdmin1 implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Admin1
    {
        if (! is_string($value) || ! str_contains($value, '.')) {
            return null;
        }
        [$country, $code] = explode('.', $value, 2);

        return app(GeonamesRepository::class)->admin1Code($country, $code);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Admin1) {
            return $value->geonamesCode();
        }
        if (! is_string($value) || ! str_contains($value, '.')) {
            throw NotFoundException::for('admin1 (expected a code like TH.50)', is_scalar($value) ? (string) $value : gettype($value));
        }
        [$country, $code] = explode('.', $value, 2);
        $found = app(GeonamesRepository::class)->admin1Code($country, $code);

        return ($found ?? throw NotFoundException::for('admin1', $value))->geonamesCode();
    }
}
