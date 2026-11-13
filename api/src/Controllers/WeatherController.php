<?php

declare(strict_types=1);

namespace ADS\TakeHome\Controllers;

use ADS\TakeHome\Domain\DailyWeather;
use ADS\TakeHome\Domain\DateRange;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Services\WeatherService;

/**
 * Napi időjárási adatok városra (és a 3. fázistól régióra).
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
            "startDate" => $range->startIso(),
            "endDate" => $range->endIso(),
            "units" => self::UNITS,
            "days" => array_map(self::formatDay(...), $report->days),
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
     * @return array{date: string, minTemp: float|null, maxTemp: float|null, precipitation: float|null}
     */
    private static function formatDay(DailyWeather $day): array
    {
        return [
            "date" => $day->date,
            "minTemp" => $day->minTemp,
            "maxTemp" => $day->maxTemp,
            "precipitation" => $day->precipitation,
        ];
    }
}
