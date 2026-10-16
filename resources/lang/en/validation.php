<?php

return [
    'country' => 'The :attribute must be a country code (ISO 3166-1 alpha-2, alpha-3 or numeric).',
    'admin1' => 'The :attribute must be a first-level division (GeoNames admin1 code) of the selected country.',
    'city' => 'The :attribute must be a GeoNames city id in the selected country.',
    'postal_code' => 'The :attribute must be a postal code in the format of the selected country.',
    'postal_code_unknown' => 'The :attribute is not a known postal code of the selected country.',
    'postal_elsewhere' => 'Thai postal codes are not in the GeoNames data; check them with farzai/thailand-address.',
    'not_fetched' => 'The GeoNames data needed to check :attribute is not downloaded yet (php artisan geonames:fetch).',
];
