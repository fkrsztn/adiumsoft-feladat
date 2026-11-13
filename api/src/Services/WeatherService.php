<?php

declare(strict_types=1);

namespace ADS\TakeHome\Services;

use ADS\TakeHome\Domain\City;
use ADS\TakeHome\Domain\DateRange;
use ADS\TakeHome\Domain\RegionAverager;
use ADS\TakeHome\Domain\WeatherSummary;
use ADS\TakeHome\Exceptions\NotFoundException;
use ADS\TakeHome\Repositories\CityRepository;
use ADS\TakeHome\Repositories\WeatherRepository;

/**
 * Időjárási kimutatások összeállítása városra és régióra.
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

        return new CityWeatherReport($city, $range, $days, WeatherSummary::fromDays($days));
    }

    /**
     * @param string $region A régió pontos neve, pl. „Dunántúl”.
     *
     * @throws NotFoundException ha nincs ilyen nevű régió.
     */
    public function getRegionWeather(string $region, DateRange $range): RegionWeatherReport
    {
        // Pontos egyezést várunk el. Az adatbázis összevetése kis- és
        // nagybetűre, valamint ékezetre sem érzékeny („dunantul” is egyezne),
        // ezért nem az SQL-re bízzuk.
        if (!in_array($region, $this->cities->findRegionNames(), true)) {
            throw new NotFoundException("Nincs „{$region}” nevű régió.");
        }

        $cities = $this->cities->findByRegion($region);
        $cityIds = array_map(fn (City $city) => $city->id, $cities);
        $days = RegionAverager::average($this->weather->findDailyByCities($cityIds, $range));

        return new RegionWeatherReport($region, $cities, $range, $days, WeatherSummary::fromDays($days));
    }
}
