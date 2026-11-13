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
}
