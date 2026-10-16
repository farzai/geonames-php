<?php

declare(strict_types=1);

namespace Farzai\Geonames\Source;

use PDO;
use Pdo\Sqlite;
use RuntimeException;

/**
 * Nearest cities from a country's SQLite file, through its `cities_rtree` R*Tree
 * (columns id, min_lat, max_lat, min_lon, max_lon). The file is opened read-only. The
 * search box doubles until the answer cannot change, so the result is exact, not
 * approximate.
 */
final class SqliteNearest
{
    private const KM_PER_DEGREE = 111.195;

    /**
     * @return list<array<string, mixed>>
     */
    public static function find(string $path, float $lat, float $lon, int $limit): array
    {
        if (! extension_loaded('pdo_sqlite')) {
            throw new RuntimeException('Nearest-city lookups read SQLite files and need the pdo_sqlite extension.');
        }

        $pdo = self::open($path);
        $country = (string) ($pdo->query("SELECT value FROM meta WHERE key = 'country_code'")->fetchColumn() ?: '');

        $statement = $pdo->prepare(
            'SELECT c.* FROM cities_rtree r JOIN cities c ON c.geoname_id = r.id '
            .'WHERE r.min_lat <= :max_lat AND r.max_lat >= :min_lat AND r.min_lon <= :max_lon AND r.max_lon >= :min_lon '
            ."AND c.status = 'active'"
        );

        $span = 0.5;
        while (true) {
            $lonSpan = min(360.0, $span / max(cos(deg2rad($lat)), 0.01));
            $wraps = $lon + $lonSpan > 180.0 || $lon - $lonSpan < -180.0;
            $statement->execute([
                'min_lat' => $lat - $span, 'max_lat' => $lat + $span,
                'min_lon' => $wraps ? -180.0 : $lon - $lonSpan, 'max_lon' => $wraps ? 180.0 : $lon + $lonSpan,
            ]);

            $found = [];
            foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $row['distance_km'] = round(Distance::km($lat, $lon, (float) $row['lat'], (float) $row['lon']), 3);
                $found[] = $row;
            }
            usort($found, fn (array $a, array $b): int => $a['distance_km'] <=> $b['distance_km']);

            $settled = count($found) >= $limit && $found[$limit - 1]['distance_km'] <= $span * self::KM_PER_DEGREE;
            if ($settled || $span >= 180.0) {
                return array_map(fn (array $row): array => self::row($row, $country), array_slice($found, 0, $limit));
            }
            $span *= 2;
        }
    }

    private static function open(string $path): PDO
    {
        $readOnly = class_exists(Sqlite::class)
            ? [Sqlite::ATTR_OPEN_FLAGS => Sqlite::OPEN_READONLY]
            : [PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY];

        return new PDO('sqlite:'.$path, null, null, $readOnly + [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function row(array $row, string $country): array
    {
        $names = json_decode((string) ($row['names'] ?? '{}'), true);
        $row['names'] = is_array($names) ? $names : [];
        $row['is_capital'] = (bool) ($row['is_capital'] ?? false);
        $row['country_code'] = $country !== '' ? $country : null;
        $row['source'] = ['dataset' => 'geonames', 'modified' => $row['source_modified'] ?? null];
        unset($row['source_modified']);

        return $row;
    }
}
