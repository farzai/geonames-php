<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Console;

use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Laravel\Seeding\Importer;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

final class SeedCommand extends Command
{
    protected $signature = 'geonames:seed
        {countries?* : ISO 3166-1 alpha-2 codes (default: every country that was fetched)}
        {--datasets=countries,admin1,admin2,cities : countries, admin1, admin2, cities, postal_codes, alternate_names}';

    protected $description = 'Copy fetched GeoNames data into the geonames_* tables (run the migration first)';

    public function handle(GeonamesRepository $repository, ConnectionInterface $db): int
    {
        $datasets = array_values(array_filter(explode(',', (string) $this->option('datasets'))));
        $counts = (new Importer($repository, $db, (array) config('geonames.tables')))->import((array) $this->argument('countries'), $datasets);

        foreach ($counts as $dataset => $rows) {
            $this->line(sprintf('  %-16s %d rows', $dataset, $rows));
        }
        $this->info('Snapshot '.$repository->snapshot().' imported.');

        return self::SUCCESS;
    }
}
