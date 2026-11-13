<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Módusz: a leggyakoribb érték(ek) és az előfordulásuk száma.
 *
 * Holtversenyben minden leggyakoribb értéket tartalmaz, növekvő sorrendben.
 * Ha nincs módusz (nincs adat, vagy minden érték csak egyszer fordul elő),
 * a lista üres és a darabszám 0.
 */
final readonly class Mode
{
    /**
     * @param list<float> $values
     */
    public function __construct(
        public array $values,
        public int $count,
    ) {
    }

    public static function none(): self
    {
        return new self([], 0);
    }
}
