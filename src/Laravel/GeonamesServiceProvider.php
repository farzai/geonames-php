<?php

declare(strict_types=1);

namespace Farzai\Geonames\Laravel;

use Farzai\Geonames\GeonamesRepository;
use Farzai\Geonames\Laravel\Console\FetchCommand;
use Farzai\Geonames\Laravel\Console\SeedCommand;
use Farzai\Geonames\Laravel\Rules\GeonamesAdmin1;
use Farzai\Geonames\Laravel\Rules\GeonamesCity;
use Farzai\Geonames\Laravel\Rules\GeonamesCountry;
use Farzai\Geonames\Laravel\Rules\GeonamesPostalCode;
use Farzai\Geonames\Snapshot\Pin;
use Farzai\Geonames\Snapshot\SnapshotStore;
use Farzai\Geonames\Snapshot\StreamTransport;
use Farzai\Geonames\Snapshot\Transport;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;

final class GeonamesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/geonames.php', 'geonames');

        $this->app->singleton(Pin::class, fn (): Pin => Pin::bundled());
        $this->app->singleton(Transport::class, fn (): Transport => new StreamTransport);
        $this->app->singleton(SnapshotStore::class, fn ($app): SnapshotStore => new SnapshotStore((string) $app['config']->get('geonames.path')));
        $this->app->singleton(GeonamesRepository::class, fn ($app): GeonamesRepository => GeonamesRepository::default(
            $app->make(SnapshotStore::class),
            $app->make(Pin::class),
            $app['config']->get('geonames.snapshot') ?: null,
        ));
    }

    public function boot(): void
    {
        $root = dirname(__DIR__, 2);

        $this->loadTranslationsFrom($root.'/resources/lang', 'geonames');
        $this->registerRules();

        if ($this->app->runningInConsole()) {
            $this->commands([FetchCommand::class, SeedCommand::class]);
            $this->publishes([$root.'/config/geonames.php' => config_path('geonames.php')], 'geonames-config');
            $this->publishes([$root.'/resources/lang' => $this->app->langPath('vendor/geonames')], 'geonames-lang');
            $this->publishes([
                $root.'/database/migrations/create_geonames_tables.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_geonames_tables.php'),
            ], 'geonames-migrations');
            $this->publishes([$root.'/database/seeders/GeonamesSeeder.php' => database_path('seeders/GeonamesSeeder.php')], 'geonames-seeder');
        }
    }

    private function registerRules(): void
    {
        // String rules: geonames_country, geonames_admin1:country_field, ...
        foreach ([
            'geonames_country' => GeonamesCountry::class,
            'geonames_admin1' => GeonamesAdmin1::class,
            'geonames_city' => GeonamesCity::class,
            'geonames_postal_code' => GeonamesPostalCode::class,
        ] as $name => $class) {
            $this->app['validator']->extend($name, function (string $attribute, mixed $value, array $parameters, $validator) use ($class, $name): bool {
                $rule = $class::make();
                if (($parameters[0] ?? null) !== null) {
                    $rule->forCountryField($parameters[0]);
                }
                $rule->setData($validator->getData());
                $failed = false;
                $attributeName = $attribute;
                $rule->validate($attribute, $value, function (string $message, ?string $attribute = null) use (&$failed, $validator, $name, $attributeName): PotentiallyTranslatedString {
                    $failed = true;
                    // The translated message of this failure (a rule has several), in the current locale.
                    $validator->setCustomMessages(["{$attributeName}.{$name}" => $message]);

                    return new PotentiallyTranslatedString($message, $validator->getTranslator());
                });

                return ! $failed;
            }, null);
        }

        // Rule::geonamesCountry(), Rule::geonamesAdmin1()->forCountryField('country'), ...
        Rule::macro('geonamesCountry', fn (): GeonamesCountry => GeonamesCountry::make());
        Rule::macro('geonamesAdmin1', fn (): GeonamesAdmin1 => GeonamesAdmin1::make());
        Rule::macro('geonamesCity', fn (): GeonamesCity => GeonamesCity::make());
        Rule::macro('geonamesPostalCode', fn (): GeonamesPostalCode => GeonamesPostalCode::make());
    }
}
