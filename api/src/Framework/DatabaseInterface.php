<?php

declare(strict_types=1);

namespace ADS\TakeHome\Framework;

/**
 * Adatbázis-lekérdező.
 *
 * Az alkalmazás kódja ettől az interface-től függ, nem a konkrét MySQL
 * osztálytól, így a tesztekben valódi adatbázis nélkül, egy egyszerű
 * helyettesítővel is futtatható.
 */
interface DatabaseInterface
{
    /**
     * Végrehajtja a lekérdezést, és az eredményt asszociatív tömbök
     * listájaként adja vissza.
     *
     * @param array<string, scalar|null> $params Név szerinti paraméterek,
     * pl. `[":cityId" => 1]`. Pozícionális paramétereket nem fogad el.
     *
     * @return list<array<string, scalar|null>>
     */
    public function queryAssoc(string $sql, array $params = []): array;
}
