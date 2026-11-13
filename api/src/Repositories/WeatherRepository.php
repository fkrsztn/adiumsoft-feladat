<?php

declare(strict_types=1);

namespace ADS\TakeHome\Repositories;

use ADS\TakeHome\Domain\DailyWeather;
use ADS\TakeHome\Domain\DateRange;
use ADS\TakeHome\Framework\DatabaseInterface;

/**
 * Napi időjárási adatok lekérdezése.
 *
 * A három adattáblát (minimum, maximum, csapadék) napra és városra
 * összekapcsolja, és az adatbázis tárolási sajátosságait (dátum egész
 * számként, `-999` a hiányzó mérésre) itt fordítja le.
 */
final class WeatherRepository
{
    /**
     * A forrásadatban a hiányzó mérés jelölése. Mindhárom adattáblában
     * előfordul (pl. Keszthely hőmérséklete 1955–1957 között végig hiányzik).
     */
    private const MISSING_VALUE = -999.0;

    public function __construct(
        private readonly DatabaseInterface $db,
    ) {
    }

    /**
     * A megadott városok napi adatai az időszakban, dátum szerint növekvő
     * sorrendben, városonként csoportosítva.
     *
     * Ha egy városra nincs adat, az nem szerepel a visszaadott tömbben.
     *
     * @param list<int> $cityIds
     *
     * @return array<int, list<DailyWeather>> Városazonosító => napi adatok.
     */
    public function findDailyByCities(array $cityIds, DateRange $range): array
    {
        if ($cityIds === []) {
            return [];
        }

        $params = [
            ":startDate" => $range->startAsDbDate(),
            ":endDate" => $range->endAsDbDate(),
        ];
        // Az IN listába csak a paraméterek NEVEI kerülnek (:city0, :city1, …),
        // az értékek továbbra is prepared statement paraméterként utaznak.
        $placeholders = [];
        foreach ($cityIds as $index => $cityId) {
            $placeholders[] = ":city{$index}";
            $params[":city{$index}"] = $cityId;
        }
        $cityList = implode(", ", $placeholders);

        // Minden naphoz van minimum- és maximumsor (ellenőrizve: a táblák teljesek,
        // a hiányzó mérést a -999 jelzi, nem a sor hiánya). A csapadékot
        // biztonságból LEFT JOIN-nal kötjük, hogy egy hiányzó sor se ejtse ki a napot.
        $sql = <<<SQL
            SELECT
                mn.city_id AS cityId,
                mn.date AS date,
                mn.min_temp AS minTemp,
                mx.max_temp AS maxTemp,
                p.precipitation AS precipitation
            FROM minimum_temperatures mn
            JOIN maximum_temperatures mx ON mx.city_id = mn.city_id AND mx.date = mn.date
            LEFT JOIN precipitation p ON p.city_id = mn.city_id AND p.date = mn.date
            WHERE mn.city_id IN ({$cityList})
                AND mn.date BETWEEN :startDate AND :endDate
            ORDER BY mn.city_id, mn.date
            SQL;

        $result = [];
        foreach ($this->db->queryAssoc($sql, $params) as $row) {
            $result[(int) $row["cityId"]][] = new DailyWeather(
                date: self::toIsoDate((int) $row["date"]),
                minTemp: self::toMeasurement($row["minTemp"]),
                maxTemp: self::toMeasurement($row["maxTemp"]),
                precipitation: self::toMeasurement($row["precipitation"]),
            );
        }
        return $result;
    }

    /**
     * Adatbázis dátum (`ÉÉÉÉHHNN` egész szám) → ISO dátum (`ÉÉÉÉ-HH-NN`).
     */
    private static function toIsoDate(int $dbDate): string
    {
        $digits = sprintf("%08d", $dbDate);
        return substr($digits, 0, 4) . "-" . substr($digits, 4, 2) . "-" . substr($digits, 6, 2);
    }

    /**
     * Mért érték: `null`, ha nincs mérés (hiányzó sor vagy -999), egyébként
     * egy tizedesre kerekítve. Ez a forrás pontossága, és így a FLOAT oszlop
     * tárolási pontatlansága sem jelenhet meg a válaszban.
     */
    private static function toMeasurement(bool|float|int|string|null $value): float|null
    {
        if ($value === null || (float) $value === self::MISSING_VALUE) {
            return null;
        }
        return round((float) $value, 1);
    }
}
