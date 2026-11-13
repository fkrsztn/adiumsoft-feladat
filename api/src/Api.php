<?php

declare(strict_types=1);

namespace ADS\TakeHome;

use ADS\TakeHome\Controllers\CityController;
use ADS\TakeHome\Controllers\WeatherController;
use ADS\TakeHome\Exceptions\NotFoundException;
use ADS\TakeHome\Exceptions\ValidationException;
use ADS\TakeHome\Framework\DatabaseInterface;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Framework\Router;
use ADS\TakeHome\Repositories\CityRepository;
use ADS\TakeHome\Repositories\WeatherRepository;
use ADS\TakeHome\Services\WeatherService;
use Closure;

/**
 * Az alkalmazás összeállítása: objektumok létrehozása és a végpontok regisztrálása.
 *
 * Az adatbázist kívülről kapja, így a tesztek ugyanezt a routert egy
 * memóriában dolgozó helyettesítővel futtathatják.
 */
final class Api
{
    public static function createRouter(DatabaseInterface $db, bool $debug = false): Router
    {
        $cityRepository = new CityRepository($db);
        $weatherService = new WeatherService($cityRepository, new WeatherRepository($db));

        $cityController = new CityController($cityRepository);
        $weatherController = new WeatherController($weatherService);

        $router = new Router(debug: $debug);
        $router->addRoute("GET", "/cities", self::withErrorResponses($cityController->listCities(...)));
        $router->addRoute("GET", "/regions", self::withErrorResponses($cityController->listRegions(...)));
        $router->addRoute("GET", "/weather/city", self::withErrorResponses($weatherController->cityWeather(...)));
        return $router;
    }

    /**
     * A felhasználói hibákat jelző kivételeket HTTP válasszá alakítja.
     *
     * Minden más kivétel továbbmegy a Routerhez, ami 500-as választ ad.
     *
     * @param Closure(HttpRequest):HttpResponseInterface $handler
     *
     * @return Closure(HttpRequest):HttpResponseInterface
     */
    private static function withErrorResponses(Closure $handler): Closure
    {
        return function (HttpRequest $request) use ($handler): HttpResponseInterface {
            try {
                return $handler($request);
            } catch (ValidationException $e) {
                return new JsonHttpResponse(["error" => $e->getMessage()], 400);
            } catch (NotFoundException $e) {
                return new JsonHttpResponse(["error" => $e->getMessage()], 404);
            }
        };
    }
}
