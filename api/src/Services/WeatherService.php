<?php

declare(strict_types=1);

namespace ADS\TakeHome\Services;

use ADS\TakeHome\Domain\DateRange;
use ADS\TakeHome\Exceptions\NotFoundException;
use ADS\TakeHome\Repositories\CityRepository;
use ADS\TakeHome\Repositories\WeatherRepository;

/**
 * Időjárási kimutatások összeállítása városra (és a 3. fázistól régióra).
 */
final class WeatherService
{
    public function __construct(
        private readonly CityRepository $cities,
        private readonly WeatherRepository $weather,
    ) {
    }

    /**
     * @throws NotFoundException ha nincs ilyen azonosítójú város.
     */
    public function getCityWeather(int $cityId, DateRange $range): CityWeatherReport
    {
        $city = $this->cities->findById($cityId)
            ?? throw new NotFoundException("Nincs {$cityId} azonosítójú város.");

        $days = $this->weather->findDailyByCities([$city->id], $range)[$city->id] ?? [];

        return new CityWeatherReport($city, $range, $days);
    }
}
