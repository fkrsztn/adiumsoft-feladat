<?php

declare(strict_types=1);

namespace ADS\TakeHome\Services;

use ADS\TakeHome\Domain\City;
use ADS\TakeHome\Domain\DailyWeather;
use ADS\TakeHome\Domain\DateRange;

/**
 * Egy város időjárása egy időszakban.
 */
final readonly class CityWeatherReport
{
    /**
     * @param list<DailyWeather> $days Dátum szerint növekvő sorrendben.
     */
    public function __construct(
        public City $city,
        public DateRange $range,
        public array $days,
    ) {
    }
}
