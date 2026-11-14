<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Egy nap időjárási adatai egy mérőállomáson.
 *
 * A hőmérséklet Celsius fokban, a csapadék milliméterben értendő. Bármelyik
 * érték lehet `null`: a forrásadatban mindhárom mérésnél vannak hiányzó napok.
 */
final readonly class DailyWeather
{
    /**
     * @param string $date A nap ISO formátumban, pl. `2023-01-15`.
     */
    public function __construct(
        public string $date,
        public float|null $minTemp,
        public float|null $maxTemp,
        public float|null $precipitation,
    ) {
    }

    /**
     * Átlaghőmérséklet: (minimum + maximum) / 2, egy tizedesre kerekítve.
     *
     * `null`, ha a minimum vagy a maximum hiányzik.
     */
    public function avgTemp(): float|null
    {
        if ($this->minTemp === null || $this->maxTemp === null) {
            return null;
        }
        return Rounding::meanOfMeasurements([$this->minTemp, $this->maxTemp]);
    }
}
