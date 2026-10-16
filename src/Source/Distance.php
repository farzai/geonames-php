<?php

declare(strict_types=1);

namespace Farzai\Geonames\Source;

final class Distance
{
    private const EARTH_RADIUS_KM = 6371.0088;

    /** Haversine, in kilometres. */
    public static function km(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * self::EARTH_RADIUS_KM * asin(min(1.0, sqrt($a)));
    }
}
