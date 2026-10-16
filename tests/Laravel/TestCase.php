<?php

declare(strict_types=1);

namespace Farzai\Geonames\Tests\Laravel;

use Farzai\Geonames\Laravel\GeonamesServiceProvider;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotFetcher;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Snapshot\Transport;
use Farzai\Geonames\Tests\Support\FakeTransport;
use Farzai\Geonames\Tests\Support\FixtureSnapshot;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $store;

    protected FixtureSnapshot $fixture;

    protected function getPackageProviders($app): array
    {
        return [GeonamesServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->fixture = new FixtureSnapshot;
        $this->store = sys_get_temp_dir().'/geonames-laravel-'.bin2hex(random_bytes(6));
        $app['config']->set('geonames.path', $this->store);
        $app['config']->set('geonames.snapshot', FixtureSnapshot::SNAPSHOT);
        $app['config']->set('geonames.base_url', 'https://data.test/geonames/v1');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app->instance(Pin::class, $this->fixture->pin());
        $app->instance(Transport::class, new FakeTransport($this->fixture->routes()));
    }

    /**
     * Put the fixture snapshot in the store the way `geonames:fetch` would.
     *
     * @param  list<string>  $countries
     * @param  list<string>  $datasets
     */
    protected function fetch(array $countries = [], array $datasets = ['countries', 'admin1'], bool $sqlite = false): void
    {
        (new SnapshotFetcher(
            new FakeTransport($this->fixture->routes()),
            new SnapshotStore($this->store),
            $this->fixture->pin(),
            'https://data.test/geonames/v1',
        ))->fetch($countries, $datasets, $sqlite);
    }

    protected function tearDown(): void
    {
        $this->remove($this->store);
        parent::tearDown();
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }
        foreach (is_dir($path) ? (scandir($path) ?: []) : [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                $this->remove($path.'/'.$entry);
            }
        }
        @rmdir($path);
    }
}
