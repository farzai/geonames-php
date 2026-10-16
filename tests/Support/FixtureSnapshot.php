<?php

declare(strict_types=1);

namespace Farzai\Geonames\Tests\Support;

use Farzai\Geonames\Snapshot\Pin;

/**
 * A tiny snapshot in the real layout and schema, built on disk with real sha256
 * values, so the fetcher and the reader are tested against what the pipeline
 * publishes without the network. Three countries: TH (cities, admin2, no postal
 * file), US (everything) and CN (a postal file with partial precision).
 */
final class FixtureSnapshot
{
    public const SNAPSHOT = '2030-01-01.1';

    /** @var array<string, string> relative path => bytes */
    public array $files = [];

    public string $manifestBody = '';

    public string $latestBody = '';

    public string $snapshotsBody = '';

    public function __construct(
        public readonly string $schemaVersion = '1.4.0',
        public readonly string $status = 'published',
    ) {
        $this->build();
    }

    public function pin(): Pin
    {
        return new Pin(self::SNAPSHOT, hash('sha256', $this->manifestBody), '1.0.0');
    }

    /** @return array<string, string> url path (after the base) => body */
    public function routes(): array
    {
        $routes = [
            'latest.json' => $this->latestBody,
            'snapshots.json' => $this->snapshotsBody,
            self::SNAPSHOT.'/manifest.json' => $this->manifestBody,
        ];
        foreach ($this->files as $path => $bytes) {
            $routes[self::SNAPSHOT.'/'.$path] = $bytes;
        }

        return $routes;
    }

    private function build(): void
    {
        $env = fn (array $data): string => json_encode([
            'schema_version' => $this->schemaVersion, 'snapshot' => self::SNAPSHOT, 'data' => $data,
            'a_field_added_in_a_newer_minor' => true,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $ndjson = fn (string $dataset, array $rows): string => gzencode(
            json_encode(['schema_version' => $this->schemaVersion, 'snapshot' => self::SNAPSHOT, 'dataset' => $dataset])."\n"
            .implode("\n", array_map(fn ($r) => json_encode($r, JSON_UNESCAPED_UNICODE), $rows))."\n"
        );

        $this->files['countries.json'] = $env([
            $this->country(1605651, 'TH', 'THA', '764', 'Thailand', 'ไทย', 'Asia/Bangkok', 'THB', '66', 'Bangkok'),
            $this->country(6252001, 'US', 'USA', '840', 'United States', 'สหรัฐอเมริกา', 'America/New_York', 'USD', '1', 'Washington', '^\\d{5}(-\\d{4})?$'),
            $this->country(1814991, 'CN', 'CHN', '156', 'China', 'จีน', 'Asia/Shanghai', 'CNY', '86', 'Beijing', '^\\d{6}$'),
        ]);
        $this->files['kinds.json'] = $env([
            ['feature_class' => 'P', 'feature_code' => 'PPL', 'kind' => 'city', 'admin_seat_level' => null, 'name' => 'populated place', 'description' => 'a city, town, village'],
            ['feature_class' => 'P', 'feature_code' => 'PPLA2', 'kind' => 'seat', 'admin_seat_level' => 2, 'name' => 'seat of a second-order division', 'description' => 'seat'],
        ]);
        $this->files['time_zones.json'] = $env([
            ['country_code' => 'TH', 'time_zone' => 'Asia/Bangkok'],
            ['country_code' => 'US', 'time_zone' => 'America/New_York'],
        ]);
        $this->files['admin1.json'] = $env([
            $this->admin1(1153668, 'TH', '50', 'Chiang Mai', 'เชียงใหม่', 'TH-50'),
            $this->admin1(1609348, 'TH', '40', 'Bangkok', 'กรุงเทพมหานคร', 'TH-10'),
            $this->admin1(5332921, 'US', 'CA', 'California', null, 'US-CA'),
        ]);

        $this->files['by-country/TH/cities.ndjson.gz'] = $ndjson('cities', [
            $this->city(1609350, 'Bangkok', 'กรุงเทพมหานคร', 13.75398, 100.50144, 'capital', 'PPLC', true, 'TH', '40', null, 5104476, 15000, 'Asia/Bangkok'),
            $this->city(1153671, 'Chiang Mai', 'เชียงใหม่', 18.79038, 98.98468, 'seat', 'PPLA', false, 'TH', '50', '5001', 127240, 15000, 'Asia/Bangkok'),
            $this->city(1116760, 'Don Sak', 'ดอนสัก', 9.31676, 99.69184, 'seat', 'PPLA2', false, 'TH', '60', '8403', 12422, 5000, 'Asia/Bangkok', 'status_gone'),
        ]);
        $this->files['by-country/TH/admin2.ndjson.gz'] = $ndjson('admin2', [
            $this->admin2(1116329, 'TH', '50', '5001', 'Mueang Chiang Mai', 'เมืองเชียงใหม่'),
        ]);
        $this->files['by-country/US/cities.ndjson.gz'] = $ndjson('cities', [
            $this->city(5368361, 'Los Angeles', null, 34.05223, -118.24368, 'city', 'PPL', false, 'US', 'CA', '037', 3898747, 15000, 'America/Los_Angeles'),
            $this->city(5391959, 'San Francisco', null, 37.77493, -122.41942, 'city', 'PPL', false, 'US', 'CA', '075', 873965, 15000, 'America/Los_Angeles', 'unknown_kind'),
        ]);
        $this->files['by-country/US/admin2.ndjson.gz'] = $ndjson('admin2', [
            $this->admin2(5368381, 'US', 'CA', '037', 'Los Angeles County', null),
        ]);
        $this->files['by-country/US/postal_codes.ndjson.gz'] = $ndjson('postal_codes', [
            $this->postal('US', '90210', 'Beverly Hills', 'California', 'CA', 'Los Angeles', '037', 34.0901, -118.4065, 'full'),
            $this->postal('US', '90210', 'Beverly Hills (annex)', 'California', 'CA', 'Los Angeles', '037', 34.09, -118.41, 'full'),
        ]);
        $this->files['by-country/US/alternate_names.ndjson.gz'] = $ndjson('alternate_names', [
            ['geoname_id' => 5368361, 'lang' => 'th', 'name' => 'ลอสแอนเจลิส', 'preferred' => true, 'short' => false, 'colloquial' => false, 'historic' => false],
        ]);
        $this->files['by-country/CN/postal_codes.ndjson.gz'] = $ndjson('postal_codes', [
            $this->postal('CN', '100000', 'Beijing', 'Beijing', '22', null, null, 39.9, 116.4, 'partial'),
            $this->postal('CN', '231100', 'Hefei', 'Anhui', '01', null, null, 31.8, 117.2, 'partial'),
            $this->postal('CN', '231100', 'Hefei', 'Anhui', '01', null, null, 31.8, 117.2, 'partial'),
        ]);

        $this->files['sqlite/TH.sqlite'] = $this->sqlite('TH', [
            [1609350, 'Bangkok', '{"th":"กรุงเทพมหานคร"}', 13.75398, 100.50144, 'capital', 'PPLC', 1, '40', null, 5104476, 15000, 'active'],
            [1153671, 'Chiang Mai', '{"th":"เชียงใหม่"}', 18.79038, 98.98468, 'seat', 'PPLA', 0, '50', '5001', 127240, 15000, 'active'],
            [1116760, 'Don Sak', '{"th":"ดอนสัก"}', 9.31676, 99.69184, 'seat', 'PPLA2', 0, '60', '8403', 12422, 5000, 'abolished'],
            [1151254, 'Pattaya', '{"th":"พัทยา"}', 12.92759, 100.87745, 'city', 'PPL', 0, '20', null, 119532, 15000, 'active'],
        ]);

        $files = [];
        foreach ($this->files as $path => $bytes) {
            $files[$path] = ['sha256' => hash('sha256', $bytes), 'bytes' => strlen($bytes), 'content_type' => 'application/octet-stream'];
        }
        $this->manifestBody = json_encode([
            'schema_version' => $this->schemaVersion,
            'snapshot' => self::SNAPSHOT,
            'generated_at' => '2030-01-01T00:00:00Z',
            'previous_snapshot' => null,
            'files' => $files,
            'credits' => ['Contains Royal Mail data (c) Royal Mail copyright and database right.'],
            'something_new' => ['ignored' => true],
        ], JSON_UNESCAPED_SLASHES);
        $sha = hash('sha256', $this->manifestBody);
        $this->latestBody = json_encode(['schema_version' => $this->schemaVersion, 'snapshot' => self::SNAPSHOT, 'manifest_sha256' => $sha]);
        $this->snapshotsBody = json_encode(['schema_version' => $this->schemaVersion, 'snapshots' => [
            ['id' => self::SNAPSHOT, 'status' => $this->status, 'published_at' => '2030-01-01T00:00:00Z', 'manifest_sha256' => $sha],
        ]]);
    }

    /**
     * A per-country SQLite file in the pipeline's layout (meta, cities, cities_rtree).
     *
     * @param  list<list<mixed>>  $cities
     */
    private function sqlite(string $country, array $cities): string
    {
        $file = tempnam(sys_get_temp_dir(), 'geonames-fixture-');
        $pdo = new \PDO('sqlite:'.$file, null, null, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE meta (key TEXT PRIMARY KEY, value TEXT NOT NULL) WITHOUT ROWID');
        $pdo->exec("INSERT INTO meta VALUES ('country_code', '{$country}'), ('snapshot', '".self::SNAPSHOT."')");
        $pdo->exec('CREATE TABLE cities (geoname_id INTEGER PRIMARY KEY, name TEXT NOT NULL, ascii_name TEXT NOT NULL, names TEXT NOT NULL, wikidata_id TEXT, lat REAL NOT NULL, lon REAL NOT NULL, kind TEXT NOT NULL, feature_class TEXT NOT NULL, feature_code TEXT NOT NULL, is_capital INTEGER NOT NULL, admin_seat_level INTEGER, admin1_code TEXT, admin2_code TEXT, admin1_geoname_id INTEGER, admin2_geoname_id INTEGER, population INTEGER NOT NULL, population_tier INTEGER, elevation_m INTEGER, dem_m INTEGER, time_zone TEXT, status TEXT NOT NULL, abolished_on TEXT, replaced_by TEXT, source_modified TEXT)');
        $pdo->exec('CREATE VIRTUAL TABLE cities_rtree USING rtree(id, min_lat, max_lat, min_lon, max_lon)');
        $insert = $pdo->prepare("INSERT INTO cities (geoname_id, name, ascii_name, names, lat, lon, kind, feature_class, feature_code, is_capital, admin1_code, admin2_code, population, population_tier, time_zone, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'P', ?, ?, ?, ?, ?, ?, 'Asia/Bangkok', ?)");
        $rtree = $pdo->prepare('INSERT INTO cities_rtree VALUES (?, ?, ?, ?, ?)');
        foreach ($cities as [$id, $name, $names, $lat, $lon, $kind, $code, $capital, $a1, $a2, $pop, $tier, $status]) {
            $insert->execute([$id, $name, $name, $names, $lat, $lon, $kind, $code, $capital, $a1, $a2, $pop, $tier, $status]);
            $rtree->execute([$id, $lat, $lat, $lon, $lon]);
        }
        unset($insert, $rtree, $pdo);
        $bytes = (string) file_get_contents($file);
        @unlink($file);

        return $bytes;
    }

    private function history(): array
    {
        return ['status' => 'active', 'abolished_on' => null, 'replaced_by' => null];
    }

    private function country(int $id, string $iso2, string $iso3, string $num, string $name, string $th, string $tz, string $cur, string $call, string $capital, ?string $postalRegex = null): array
    {
        return ['geoname_id' => $id, 'iso2' => $iso2, 'iso3' => $iso3, 'iso_numeric' => $num, 'fips' => $iso2, 'name' => $name,
            'names' => ['th' => $th, 'en' => $name], 'wikidata_id' => null, 'kind' => 'country', 'capital' => $capital, 'continent' => 'AS',
            'area_sq_km' => 100, 'population' => 1000, 'currency' => ['code' => $cur, 'name' => $cur], 'calling_code' => $call, 'tld' => '.'.strtolower($iso2),
            'languages' => ['en'], 'neighbours' => [], 'time_zones' => [$tz], 'postal_code_format' => null, 'postal_code_regex' => $postalRegex,
            'source' => ['dataset' => 'geonames', 'modified' => null]] + $this->history();
    }

    private function admin1(int $id, string $cc, string $code, string $name, ?string $th, ?string $iso): array
    {
        return ['geoname_id' => $id, 'geonames_code' => "$cc.$code", 'country_code' => $cc, 'admin1_code' => $code, 'name' => $name, 'ascii_name' => $name,
            'names' => $th === null ? ['en' => $name] : ['th' => $th, 'en' => $name], 'wikidata_id' => null, 'kind' => 'admin1', 'feature_class' => 'A', 'feature_code' => 'ADM1',
            'iso_3166_2' => $iso, 'source' => ['dataset' => 'geonames', 'modified' => null]] + $this->history();
    }

    private function admin2(int $id, string $cc, string $a1, string $a2, string $name, ?string $th): array
    {
        return ['geoname_id' => $id, 'geonames_code' => "$cc.$a1.$a2", 'country_code' => $cc, 'admin1_code' => $a1, 'admin2_code' => $a2, 'admin1_geoname_id' => null,
            'name' => $name, 'ascii_name' => $name, 'names' => $th === null ? ['en' => $name] : ['th' => $th, 'en' => $name], 'wikidata_id' => null,
            'kind' => 'admin2', 'feature_class' => 'A', 'feature_code' => 'ADM2', 'iso_3166_2' => null, 'source' => ['dataset' => 'geonames', 'modified' => null]] + $this->history();
    }

    private function city(int $id, string $name, ?string $th, float $lat, float $lon, string $kind, string $code, bool $capital, string $cc, string $a1, ?string $a2, int $pop, int $tier, string $tz, ?string $quirk = null): array
    {
        $row = ['geoname_id' => $id, 'name' => $name, 'ascii_name' => $name, 'names' => $th === null ? ['en' => $name] : ['th' => $th, 'en' => $name], 'wikidata_id' => null,
            'lat' => $lat, 'lon' => $lon, 'kind' => $kind, 'feature_class' => 'P', 'feature_code' => $code, 'is_capital' => $capital, 'admin_seat_level' => null,
            'country_code' => $cc, 'admin1_code' => $a1, 'admin2_code' => $a2, 'admin1_geoname_id' => null, 'admin2_geoname_id' => null, 'population' => $pop,
            'population_tier' => $tier, 'elevation_m' => null, 'dem_m' => 10, 'time_zone' => $tz, 'source' => ['dataset' => 'geonames', 'modified' => '2026-01-01']] + $this->history();
        if ($quirk === 'unknown_kind') {
            $row['kind'] = 'future_kind';
        }
        if ($quirk === 'status_gone') {
            $row['status'] = 'abolished';
            $row['abolished_on'] = '2026-09-01';
        }

        return $row;
    }

    private function postal(string $cc, string $code, string $place, ?string $a1n, ?string $a1c, ?string $a2n, ?string $a2c, float $lat, float $lon, string $precision): array
    {
        return ['country_code' => $cc, 'postal_code' => $code, 'place_name' => $place, 'admin1_name' => $a1n, 'admin1_code' => $a1c, 'admin2_name' => $a2n, 'admin2_code' => $a2c,
            'admin3_name' => null, 'admin3_code' => null, 'lat' => $lat, 'lon' => $lon, 'accuracy' => 4, 'precision' => $precision, 'source' => 'geonames'];
    }
}
