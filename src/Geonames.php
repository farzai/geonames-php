<?php

declare(strict_types=1);

namespace Farzai\Geonames;

use Farzai\Geonames\Models\Admin1;
use Farzai\Geonames\Models\Admin2;
use Farzai\Geonames\Models\City;
use Farzai\Geonames\Models\Country;
use Farzai\Geonames\Models\PostalCode;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;

/**
 * Static entry point over a {@see GeonamesRepository}, for plain PHP and for Laravel.
 * `Geonames::swap()` puts a fake in its place in tests.
 *
 * @method static Collection<int, Country> countries()
 * @method static Country|null country(string|int $code)
 * @method static Country countryOrFail(string|int $code)
 * @method static Collection<int, Admin1> admin1(string $country)
 * @method static Admin1|null admin1Code(string $country, string $code)
 * @method static Collection<int, Admin2> admin2(string $country, ?string $admin1 = null)
 * @method static Collection<int, City> cities(string $country, ?string $admin1 = null, ?int $minPopulation = null)
 * @method static City|null city(int $id, ?string $country = null)
 * @method static Collection<int, PostalCode> postalCodes(string $country, ?string $code = null)
 * @method static Collection<int, City> search(string $term, ?string $country = null, int $limit = 20)
 * @method static Collection<int, City> nearest(float $lat, float $lon, string $country, int $limit = 5)
 * @method static list<string> timeZones(?string $country = null)
 * @method static Collection<int, array<string, mixed>> kinds()
 * @method static GeonamesRepository withAbolished()
 * @method static string snapshot()
 */
final class Geonames
{
    private static ?GeonamesRepository $repository = null;

    public static function repository(): GeonamesRepository
    {
        if (self::$repository !== null) {
            return self::$repository;
        }

        // In Laravel the container's repository (it knows the configured store) is the default.
        $container = self::container();
        if ($container !== null && $container->bound(GeonamesRepository::class)) {
            return $container->make(GeonamesRepository::class);
        }

        return self::$repository = GeonamesRepository::default();
    }

    /**
     * Use another repository (a fake, or one with a different store); `null` goes back
     * to the default.
     */
    public static function swap(?GeonamesRepository $repository): void
    {
        self::$repository = $repository;

        // Laravel: the rules, casts and the facade read the container, so rebind it too.
        $container = self::container();
        if ($container !== null && $container->bound(GeonamesRepository::class)) {
            $repository === null
                ? $container->forgetInstance(GeonamesRepository::class)
                : $container->instance(GeonamesRepository::class, $repository);
        }
    }

    private static function container(): ?Container
    {
        return class_exists(Container::class) ? Container::getInstance() : null;
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return self::repository()->{$method}(...$arguments);
    }
}
