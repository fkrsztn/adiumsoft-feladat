<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Mérőállomás (város) és a régió, amelyhez tartozik.
 */
final readonly class City
{
    public function __construct(
        public int $id,
        public string $name,
        public string $region,
    ) {
    }
}
