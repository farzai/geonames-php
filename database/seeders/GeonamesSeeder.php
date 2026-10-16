<?php

namespace Database\Seeders;

use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Laravel\Seeding\Importer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;

/**
 * Seeds the geonames_* tables from the data `php artisan geonames:fetch` downloaded.
 * Set `$countries` to limit it (an empty list is every country that was fetched).
 */
class GeonamesSeeder extends Seeder
{
    /** @var list<string> */
    protected array $countries = [];

    /** @var list<string> */
    protected array $datasets = ['countries', 'admin1', 'admin2', 'cities'];

    public function run(GeonamesRepository $repository, ConnectionInterface $db): void
    {
        $counts = (new Importer($repository, $db, (array) config('geonames.tables')))->import($this->countries, $this->datasets);

        foreach ($counts as $dataset => $rows) {
            $this->command?->info(sprintf('%s: %d rows', $dataset, $rows));
        }
    }
}
