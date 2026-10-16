<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel\Console;

use Farzai\Geonames\Console\Cli;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Snapshot\Transport;
use Illuminate\Console\Command;

final class FetchCommand extends Command
{
    protected $signature = 'geonames:fetch
        {countries?* : ISO 3166-1 alpha-2 codes}
        {--datasets= : admin1, admin2, cities, alternate_names, postal_codes, or "all" (default admin1,admin2,cities)}
        {--sqlite : also fetch each country\'s SQLite file (nearest-city lookups)}
        {--snapshot= : a snapshot id or "latest" (default: the pinned one)}';

    protected $description = 'Download and verify GeoNames snapshot files into the local store';

    public function handle(SnapshotStore $store, Transport $transport, Pin $pin): int
    {
        $argv = ['fetch', ...(array) $this->argument('countries')];
        if (is_string($this->option('datasets')) && $this->option('datasets') !== '') {
            $argv[] = '--datasets='.$this->option('datasets');
        }
        if ($this->option('sqlite')) {
            $argv[] = '--sqlite';
        }
        if (is_string($this->option('snapshot')) && $this->option('snapshot') !== '') {
            $argv[] = '--snapshot='.$this->option('snapshot');
        }

        $out = fopen('php://memory', 'w+');
        $err = fopen('php://memory', 'w+');
        $code = (new Cli($transport, (string) config('geonames.base_url'), $pin))->run($argv, $out, $err, $store);
        rewind($out);
        rewind($err);
        $this->output->write((string) stream_get_contents($out));
        $error = trim((string) stream_get_contents($err));
        if ($error !== '') {
            $this->error($error);
        }

        return $code;
    }
}
