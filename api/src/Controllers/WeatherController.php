<?php

declare(strict_types=1);

namespace ADS\TakeHome\Controllers;

use ADS\TakeHome\Domain\City;
use ADS\TakeHome\Domain\ColumnStatistics;
use ADS\TakeHome\Domain\DailyWeather;
use ADS\TakeHome\Domain\DateRange;
use ADS\TakeHome\Domain\WeatherSummary;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Services\WeatherService;

/**
 * Napi időjárási adatok városra és régióra, oszloponkénti összesítéssel.
 */
final class WeatherController
{
    /**
     * Az értékek mértékegysége a válaszban. A kliens ebből indul ki, ha
     * angolszász mértékegységre vált.
     */
    private const UNITS = [
        "temperature" => "°C",
        "precipitation" => "mm",
    ];

    public function __construct(
        private readonly WeatherService $service,
    ) {
    }

    /**
     * `GET /weather/city?cityId=1&startDate=2023-01-01&endDate=2023-12-31`
     */
    public function cityWeather(HttpRequest $request): HttpResponseInterface
    {
        $params = new QueryParameters($request->query);
        $cityId = $params->requirePositiveInt("cityId");
        $range = self::readDateRange($params);

        $report = $this->service->getCityWeather($cityId, $range);

        return new JsonHttpResponse([
            "city" => CityController::formatCity($report->city),
            ...self::formatWeather($range, $report->days, $report->summary),
        ]);
    }

    /**
     * `GET /weather/region?region=Dunántúl&startDate=2023-01-01&endDate=2023-12-31`
     */
    public function regionWeather(HttpRequest $request): HttpResponseInterface
    {
        $params = new QueryParameters($request->query);
        $region = $params->requireString("region");
        $range = self::readDateRange($params);

        $report = $this->service->getRegionWeather($region, $range);

        return new JsonHttpResponse([
            "region" => [
                "name" => $report->region,
                "cities" => array_map(
                    fn (City $city) => ["id" => $city->id, "name" => $city->name],
                    $report->cities,
                ),
            ],
            ...self::formatWeather($range, $report->days, $report->summary),
        ]);
    }

    private static function readDateRange(QueryParameters $params): DateRange
    {
        return DateRange::fromIsoStrings(
            $params->requireString("startDate"),
            $params->requireString("endDate"),
        );
    }

    /**
     * A városi és a régiós válasz közös része.
     *
     * @param list<DailyWeather> $days
     *
     * @return array<string, mixed>
     */
    private static function formatWeather(DateRange $range, array $days, WeatherSummary $summary): array
    {
        return [
            "startDate" => $range->startIso(),
            "endDate" => $range->endIso(),
            "units" => self::UNITS,
            "days" => array_map(self::formatDay(...), $days),
            "summary" => [
                "minTemp" => self::formatColumn($summary->minTemp),
                "maxTemp" => self::formatColumn($summary->maxTemp),
                "avgTemp" => self::formatColumn($summary->avgTemp),
                "precipitation" => self::formatColumn($summary->precipitation),
            ],
        ];
    }

    /**
     * @return array{date: string, minTemp: float|null, maxTemp: float|null, avgTemp: float|null, precipitation: float|null}
     */
    private static function formatDay(DailyWeather $day): array
    {
        return [
            "date" => $day->date,
            "minTemp" => $day->minTemp,
            "maxTemp" => $day->maxTemp,
            "avgTemp" => $day->avgTemp(),
            "precipitation" => $day->precipitation,
        ];
    }

    /**
     * @return array{median: float|null, mode: array{values: list<float>, count: int}}
     */
    private static function formatColumn(ColumnStatistics $column): array
    {
        return [
            "median" => $column->median,
            "mode" => [
                "values" => $column->mode->values,
                "count" => $column->mode->count,
            ],
        ];
    }
}
