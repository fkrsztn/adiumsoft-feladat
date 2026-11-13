<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Egy táblázatoszlop összesítése (a táblázat láblécéhez).
 */
final readonly class ColumnStatistics
{
    /**
     * @param float|null $median `null`, ha az oszlopban nincs mért érték.
     */
    public function __construct(
        public float|null $median,
        public Mode $mode,
    ) {
    }
}
