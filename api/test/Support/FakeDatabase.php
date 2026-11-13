<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Support;

use ADS\TakeHome\Framework\DatabaseInterface;

/**
 * Memóriában dolgozó adatbázis-helyettesítő a tesztekhez.
 *
 * Előre megadott sorokat ad vissza azokra a lekérdezésekre, amelyek SQL
 * szövege tartalmazza a megadott részletet, és rögzíti a kapott
 * lekérdezéseket, hogy a teszt a paramétereket is ellenőrizhesse.
 */
final class FakeDatabase implements DatabaseInterface
{
    /**
     * @var list<array{fragment: string, rows: list<array<string, scalar|null>>}>
     */
    private array $results = [];

    /**
     * @var list<array{sql: string, params: array<string, scalar|null>}>
     */
    public array $queries = [];

    /**
     * Az első illeszkedő részlet sorai kerülnek visszaadásra; ha egyik sem
     * illeszkedik, az eredmény üres.
     *
     * @param list<array<string, scalar|null>> $rows
     */
    public function willReturn(string $sqlFragment, array $rows): self
    {
        $this->results[] = ["fragment" => $sqlFragment, "rows" => $rows];
        return $this;
    }

    public function queryAssoc(string $sql, array $params = []): array
    {
        $this->queries[] = ["sql" => $sql, "params" => $params];
        foreach ($this->results as $result) {
            if (str_contains($sql, $result["fragment"])) {
                return $result["rows"];
            }
        }
        return [];
    }
}
