<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Egy régió városainak napi adataiból „virtuális állomást” készít: minden
 * napra és minden mért értékre a városok átlagát.
 *
 * Mezőnként csak azokat a városokat átlagolja, ahol aznap volt mérés; ha
 * egyikben sem volt, az érték `null`. Az átlaghőmérséklet ezután az átlagolt
 * minimumból és maximumból számolódik ((min + max) / 2), így a táblázat
 * minden sorában ellenőrizhető.
 */
final class RegionAverager
{
    /**
     * @param array<int, list<DailyWeather>> $daysByCity Városazonosító => napi adatok.
     *
     * @return list<DailyWeather> Dátum szerint növekvő sorrendben.
     */
    public static function average(array $daysByCity): array
    {
        $daysByDate = [];
        foreach ($daysByCity as $days) {
            foreach ($days as $day) {
                $daysByDate[$day->date][] = $day;
            }
        }
        ksort($daysByDate, SORT_STRING);

        $result = [];
        foreach ($daysByDate as $date => $days) {
            $result[] = new DailyWeather(
                date: (string) $date,
                minTemp: self::mean(array_map(fn (DailyWeather $day) => $day->minTemp, $days)),
                maxTemp: self::mean(array_map(fn (DailyWeather $day) => $day->maxTemp, $days)),
                precipitation: self::mean(array_map(fn (DailyWeather $day) => $day->precipitation, $days)),
            );
        }
        return $result;
    }

    /**
     * A mért értékek átlaga, egy tizedesre kerekítve; `null`, ha nincs mért érték.
     *
     * @param list<float|null> $values
     */
    private static function mean(array $values): float|null
    {
        $measured = array_values(array_filter($values, fn (float|null $value) => $value !== null));
        if ($measured === []) {
            return null;
        }
        return Rounding::meanOfMeasurements($measured);
    }
}
