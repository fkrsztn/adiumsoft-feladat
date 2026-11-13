<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

/**
 * Az adatbázis ismert városnév-hibáinak javítása.
 *
 * A `cities` táblában a 10-es azonosítójú város neve „Szombathely”, ugyanúgy,
 * mint a 9-esé. Az adatokat betöltő script (`migrations/003_import_weather_data.py`)
 * viszont a 10-es azonosítóhoz Túrkeve méréseit tölti be (`10: "Turkeve"`), és
 * a régió („Észak és Alföld”) is Túrkevére illik, Szombathelyre nem.
 *
 * A javítás helye az adatbázis volna (lásd `db-suggestions.md`); amíg az nem
 * történik meg, az API itt, egyetlen helyen javítja a nevet. Az adatbázis
 * javítása után ez az osztály törölhető.
 */
final class CityNameCorrections
{
    /**
     * Városazonosító => helyes név.
     */
    private const CORRECT_NAMES = [
        10 => "Túrkeve",
    ];

    public static function apply(int $cityId, string $name): string
    {
        return self::CORRECT_NAMES[$cityId] ?? $name;
    }
}
