<?php

declare(strict_types=1);

namespace ADS\TakeHome\Repositories;

use ADS\TakeHome\Domain\City;
use ADS\TakeHome\Domain\CityNameCorrections;
use ADS\TakeHome\Framework\DatabaseInterface;
use Collator;

/**
 * Városok és régiók lekérdezése a `cities` táblából.
 */
final class CityRepository
{
    private const SELECT_CITIES = "SELECT id, name, region FROM cities";

    public function __construct(
        private readonly DatabaseInterface $db,
    ) {
    }

    /**
     * Az összes város, név szerint ábécérendben.
     *
     * @return list<City>
     */
    public function findAll(): array
    {
        return $this->findSortedByName(self::SELECT_CITIES);
    }

    /**
     * A régió városai, név szerint ábécérendben. Ismeretlen régióra üres lista.
     *
     * @return list<City>
     */
    public function findByRegion(string $region): array
    {
        return $this->findSortedByName(self::SELECT_CITIES . " WHERE region = :region", [":region" => $region]);
    }

    public function findById(int $cityId): City|null
    {
        $rows = $this->db->queryAssoc(self::SELECT_CITIES . " WHERE id = :cityId", [":cityId" => $cityId]);
        return $rows === [] ? null : $this->toCity($rows[0]);
    }

    /**
     * A régiók neve, ábécérendben.
     *
     * @return list<string>
     */
    public function findRegionNames(): array
    {
        $rows = $this->db->queryAssoc("SELECT DISTINCT region FROM cities");
        $regions = array_map(fn (array $row) => (string) $row["region"], $rows);

        // Az `enum` oszlopra az SQL ORDER BY a felsorolás sorrendjét adná,
        // nem az ábécérendet, ezért itt is PHP-ban rendezünk.
        self::createCollator()->sort($regions);
        return $regions;
    }

    /**
     * @param array<string, scalar|null> $params
     *
     * @return list<City>
     */
    private function findSortedByName(string $sql, array $params = []): array
    {
        $cities = array_map($this->toCity(...), $this->db->queryAssoc($sql, $params));

        // PHP-ban rendezünk, nem SQL-ben, mert a névjavítás a lekérdezés után
        // történik, és a javított névnek is a helyére kell kerülnie.
        $collator = self::createCollator();
        usort($cities, fn (City $a, City $b) => (int) $collator->compare($a->name, $b->name));
        return $cities;
    }

    /**
     * @param array<string, scalar|null> $row
     */
    private function toCity(array $row): City
    {
        $id = (int) $row["id"];
        return new City(
            id: $id,
            name: CityNameCorrections::apply($id, (string) $row["name"]),
            region: (string) $row["region"],
        );
    }

    /**
     * Magyar ábécérend (pl. „Cs” a „C” után, „É” az „E” mellett).
     */
    private static function createCollator(): Collator
    {
        return new Collator("hu_HU");
    }
}
