<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Medián és módusz számítása. Tiszta függvények, mellékhatás nélkül.
 */
final class Statistics
{
    /**
     * A medián ennyi tizedesre kerül kerekítésre. Egy tizedes pontosságú
     * értékeknél a páros elemszámú medián (két érték átlaga) legfeljebb két
     * tizedes, így ez nem veszít információt, csak a lebegőpontos zajt tünteti el.
     */
    private const MEDIAN_DECIMALS = 2;

    /**
     * Egy oszlop mediánja és módusza. A hiányzó (`null`) értékek kimaradnak.
     *
     * @param list<float|null> $values
     */
    public static function summarize(array $values): ColumnStatistics
    {
        $measured = array_values(array_filter($values, fn (float|null $value) => $value !== null));
        return new ColumnStatistics(self::median($measured), self::mode($measured));
    }

    /**
     * Medián: a sorba rendezett értékek közül a középső; páros elemszámnál a
     * két középső átlaga. Üres listára `null`.
     *
     * @param list<float> $values
     */
    public static function median(array $values): float|null
    {
        if ($values === []) {
            return null;
        }
        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);
        $median = $count % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
        return Rounding::round($median, self::MEDIAN_DECIMALS);
    }

    /**
     * Módusz: a leggyakoribb érték(ek), egy tizedesre kerekítve összevetve.
     *
     * Holtversenyben minden leggyakoribb értéket visszaad, növekvő sorrendben.
     * Ha minden érték csak egyszer fordul elő, nincs módusz.
     *
     * @param list<float> $values
     */
    public static function mode(array $values): Mode
    {
        // Lebegőpontos szám nem lehet tömbkulcs (a PHP egésszé csonkolná),
        // ezért fix formátumú szöveg a kulcs. A %F a nyelvi beállítástól
        // független (mindig tizedespont).
        $counts = [];
        foreach ($values as $value) {
            $key = sprintf("%.1F", Rounding::toMeasurement($value));
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        if ($counts === []) {
            return Mode::none();
        }

        $maxCount = max($counts);
        if ($maxCount < 2) {
            return Mode::none();
        }

        $modes = [];
        foreach ($counts as $key => $count) {
            if ($count === $maxCount) {
                $modes[] = (float) $key;
            }
        }
        sort($modes);
        return new Mode($modes, $maxCount);
    }
}
