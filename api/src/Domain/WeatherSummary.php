<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * A táblázat láblécének adatai: oszloponként medián és módusz.
 */
final readonly class WeatherSummary
{
    public function __construct(
        public ColumnStatistics $minTemp,
        public ColumnStatistics $maxTemp,
        public ColumnStatistics $avgTemp,
        public ColumnStatistics $precipitation,
    ) {
    }

    /**
     * @param list<DailyWeather> $days
     */
    public static function fromDays(array $days): self
    {
        return new self(
            minTemp: Statistics::summarize(array_map(fn (DailyWeather $day) => $day->minTemp, $days)),
            maxTemp: Statistics::summarize(array_map(fn (DailyWeather $day) => $day->maxTemp, $days)),
            avgTemp: Statistics::summarize(array_map(fn (DailyWeather $day) => $day->avgTemp(), $days)),
            precipitation: Statistics::summarize(array_map(fn (DailyWeather $day) => $day->precipitation, $days)),
        );
    }
}
