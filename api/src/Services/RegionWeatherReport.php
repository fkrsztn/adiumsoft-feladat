<?php

declare(strict_types=1);

namespace ADS\TakeHome\Services;

use ADS\TakeHome\Domain\City;
use ADS\TakeHome\Domain\DailyWeather;
use ADS\TakeHome\Domain\DateRange;
use ADS\TakeHome\Domain\WeatherSummary;

/**
 * Egy régió városainak átlagolt időjárása egy időszakban.
 */
final readonly class RegionWeatherReport
{
    /**
     * @param list<City> $cities Az átlagolt városok, név szerint rendezve.
     * @param list<DailyWeather> $days Napi átlagok, dátum szerint növekvő sorrendben.
     */
    public function __construct(
        public string $region,
        public array $cities,
        public DateRange $range,
        public array $days,
        public WeatherSummary $summary,
    ) {
    }
}
