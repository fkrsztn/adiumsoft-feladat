<?php

declare(strict_types=1);

namespace ADS\TakeHome\Controllers;

use ADS\TakeHome\Domain\City;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Repositories\CityRepository;

/**
 * Városlista és régiólista (a kliens választólistáihoz).
 *
 * Üzleti logika nincs bennük, ezért közvetlenül a repository-t használják.
 */
final class CityController
{
    public function __construct(
        private readonly CityRepository $cities,
    ) {
    }

    /**
     * `GET /cities`
     */
    public function listCities(HttpRequest $request): HttpResponseInterface
    {
        return new JsonHttpResponse([
            "cities" => array_map(self::formatCity(...), $this->cities->findAll()),
        ]);
    }

    /**
     * `GET /regions`
     */
    public function listRegions(HttpRequest $request): HttpResponseInterface
    {
        return new JsonHttpResponse([
            "regions" => $this->cities->findRegionNames(),
        ]);
    }

    /**
     * @return array{id: int, name: string, region: string}
     */
    public static function formatCity(City $city): array
    {
        return [
            "id" => $city->id,
            "name" => $city->name,
            "region" => $city->region,
        ];
    }
}
