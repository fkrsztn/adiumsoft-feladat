<?php

declare(strict_types=1);

namespace ADS\TakeHome\Controllers;

use ADS\TakeHome\Exceptions\ValidationException;

/**
 * Query paraméterek típusos, ellenőrzött kiolvasása.
 *
 * A PHP a `?cityId[]=1` alakot tömbként adja át, ezért minden paraméternél
 * ellenőrizni kell, hogy valóban egyetlen szöveges érték-e.
 */
final class QueryParameters
{
    /**
     * @param array<string, string|list<string>> $query
     */
    public function __construct(
        private readonly array $query,
    ) {
    }

    /**
     * @throws ValidationException ha a paraméter hiányzik, üres vagy többször szerepel.
     */
    public function requireString(string $name): string
    {
        $value = $this->query[$name] ?? null;
        if ($value === null || $value === "") {
            throw new ValidationException("Hiányzó paraméter: {$name}.");
        }
        if (!is_string($value)) {
            throw new ValidationException("Érvénytelen paraméter: {$name} (csak egy érték adható meg).");
        }
        return $value;
    }

    /**
     * @throws ValidationException ha a paraméter hiányzik, vagy nem pozitív egész szám.
     */
    public function requirePositiveInt(string $name): int
    {
        $value = filter_var(
            $this->requireString($name),
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]],
        );
        if ($value === false) {
            throw new ValidationException("Érvénytelen paraméter: {$name} (pozitív egész számot vár).");
        }
        return $value;
    }
}
