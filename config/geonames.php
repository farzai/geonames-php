<?php

declare(strict_types=1);

return [

    /*
    | Where the verified snapshot files live. Reading never uses the network; only
    | `php artisan geonames:fetch` downloads, into this directory.
    */
    'path' => env('GEONAMES_PATH', storage_path('app/geonames')),

    /*
    | The snapshot to read. Leave it null to use the one this release of the package is
    | pinned to; set an id (for example "2026-10-16.1") to read another that you fetched
    | with `geonames:fetch --snapshot=<id>`.
    */
    'snapshot' => env('GEONAMES_SNAPSHOT'),

    /*
    | The snapshot server. Change it only to read from a mirror.
    */
    'base_url' => env('GEONAMES_BASE_URL', 'https://data.farzai.com/geonames/v1'),

    /*
    | Table names of the publishable migrations and the seeder.
    */
    'tables' => [
        'countries' => 'geonames_countries',
        'admin1' => 'geonames_admin1',
        'admin2' => 'geonames_admin2',
        'cities' => 'geonames_cities',
        'postal_codes' => 'geonames_postal_codes',
        'alternate_names' => 'geonames_alternate_names',
    ],
];
