<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Kerekítési szabályok egy helyen.
 */
final class Rounding
{
    /**
     * A mért értékek pontossága (a forrásadat egy tizedesre adja meg őket).
     */
    public const MEASUREMENT_DECIMALS = 1;

    /**
     * Kerekít, és a negatív nullát (-0.0) nullára cseréli, hogy a válaszban
     * ne jelenjen meg „-0” (pl. a -0.04 egy tizedesre kerekítve -0.0 lenne).
     */
    public static function round(float $value, int $decimals): float
    {
        $rounded = round($value, $decimals);
        return $rounded == 0.0 ? 0.0 : $rounded;
    }

    /**
     * A mért értékek pontosságára kerekít (egy tizedes).
     */
    public static function toMeasurement(float $value): float
    {
        return self::round($value, self::MEASUREMENT_DECIMALS);
    }

    /**
     * Mért értékek átlaga, a mérés pontosságára (egy tizedes) kerekítve.
     *
     * Az értékeket előbb egész tizedekre váltja, mert azok összege pontos.
     * Lebegőpontos összeadással a „fél” határeset elcsúszhat: például
     * (-1.3 + 1.4) / 2 a gépen 0.0499999…, ami 0.0-ra kerekedne 0.1 helyett.
     * Egész tizedekben (-13 + 14) / 2 = 0.5 tized, ez a nullától távolodva
     * 1 tized, vagyis 0.1, ugyanúgy, ahogy a többi kerekítés.
     *
     * @param non-empty-list<float> $values
     */
    public static function meanOfMeasurements(array $values): float
    {
        $scale = 10 ** self::MEASUREMENT_DECIMALS;
        $sumOfTenths = 0;
        foreach ($values as $value) {
            $sumOfTenths += (int) round($value * $scale);
        }
        return self::round(round($sumOfTenths / count($values)) / $scale, self::MEASUREMENT_DECIMALS);
    }
}
