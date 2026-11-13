# Adatbázis struktúra módosítás javaslatok

<!-- ez a fájl egy nem kötelező feladat része -->

Fontossági sorrendben. Minden állítás mögött lekérdezés vagy mérés áll a `weather` sémán
(MySQL 8.0).

| # | Probléma | Javaslat |
|---|----------|----------|
| 1 | A 10-es város neve hibás | `name = 'Túrkeve'`, `UNIQUE (name)` |
| 2 | `-999` a hiányzó mérésre | `NULL` |
| 3 | Nincs kulcs, index, idegen kulcs | `PRIMARY KEY (city_id, date)`, FK |
| 4 | `FLOAT` | `DECIMAL` |
| 5 | Dátum `bigint`-ként | `DATE` |
| 6 | Három adattábla | Egy `daily_weather` tábla |
| 7 | Régió `enum`-ként | `regions` tábla + FK |
| 8 | Ellentmondó adat | `CHECK` megkötések |
| 9 | Import scriptek | Robusztusabb import |

## 1. A 10-es város valójában Túrkeve

A 9-es és a 10-es város neve is „Szombathely”, de a 10-es Túrkeve:

- az import script (`003_import_weather_data.py`) a 10-eshez a `Turkeve` CSV-ket tölti be;
- a régiója „Észak és Alföld”, ami Túrkevére illik, Szombathelyre nem;
- a mérései eltérnek (2023-07-15 maximum: 32,3 °C és 32,9 °C).

```sql
UPDATE cities SET name = 'Túrkeve' WHERE id = 10;
ALTER TABLE cities ADD CONSTRAINT uq_cities_name UNIQUE (name);
```

Addig az API javítja a nevet (`api/src/Domain/CityNameCorrections.php`); a DB javítása után
ez törölhető.

## 2. `-999` helyett `NULL`

A hiányzó mérés `-999`, mindhárom adattáblában. Számként átcsúszik minden `AVG()`-n és
mediánon, ha valaki nem szűri ki.

| Tábla | `-999` sor |
|-------|-----------:|
| `minimum_temperatures` | 4009 |
| `maximum_temperatures` | 3893 |
| `precipitation` | 2451 |

Érintett: Túrkeve, Keszthely (1955–1957 között a hőmérséklet teljesen hiányzik),
Nyíregyháza, Miskolc, Sopron.

```sql
ALTER TABLE minimum_temperatures MODIFY min_temp DECIMAL(4,1) NULL;
UPDATE minimum_temperatures SET min_temp = NULL WHERE min_temp = -999;
-- ugyanígy a másik két táblára
```

Addig az API fordítja `null`-ra (`api/src/Repositories/WeatherRepository.php`).

## 3. Kulcs és index az adattáblákon

Nincs elsődleges kulcs, egyediség, index és idegen kulcs. Duplikált nap és nem létező város is
bekerülhet, és minden lekérdezés teljes táblaolvasás (`EXPLAIN`: `type: ALL`, ~450 ezer sor).

Mérés, egy év (2023):

| Lekérdezés | Index nélkül | Indexszel |
|------------|-------------:|----------:|
| Egy város, háromtáblás join | ~770 ms | ~23 ms |
| Egy régió, join + `AVG` + `GROUP BY` | ~750 ms | ~95 ms |

```sql
ALTER TABLE minimum_temperatures
    ADD PRIMARY KEY (city_id, date),
    ADD CONSTRAINT fk_min_city FOREIGN KEY (city_id) REFERENCES cities (id);
-- ugyanígy a másik két táblára
```

## 4. `FLOAT` helyett `DECIMAL`

A `FLOAT` közelítő: a 32,8 tárolva `32.799999237061`.

```sql
SELECT COUNT(*) FROM maximum_temperatures WHERE max_temp = 32.8;           -- 0
SELECT COUNT(*) FROM maximum_temperatures WHERE ROUND(max_temp, 1) = 32.8; -- 370
```

Budapest 2023-as maximumainak összege `FLOAT`-tal 6689.299995…, `DECIMAL`-lal 6689.3.
A forrás egy tizedes pontosságú (mért tartomány: -30,2 … 41,3 °C, 0 … 115,4 mm):

```sql
ALTER TABLE minimum_temperatures MODIFY min_temp      DECIMAL(4,1) NULL;
ALTER TABLE maximum_temperatures MODIFY max_temp      DECIMAL(4,1) NULL;
ALTER TABLE precipitation        MODIFY precipitation DECIMAL(5,1) NULL;
```

## 5. Dátum `DATE` típussal

A `20230115` alakú `bigint` nem garantál létező napot (a `20230230` is beírható), és nem
működnek rá a dátumfüggvények.

```sql
ALTER TABLE minimum_temperatures ADD COLUMN day DATE;
UPDATE minimum_temperatures SET day = STR_TO_DATE(date, '%Y%m%d');
```

## 6. Egy tábla három helyett

A három tábla ugyanarra a (város, nap) párra vonatkozik, mégis minden napi adathoz
háromtáblás join kell. Egy tábla a 2–5. pontot egyszerre megoldja:

```sql
CREATE TABLE daily_weather (
    city_id       INT          NOT NULL,
    day           DATE         NOT NULL,
    min_temp      DECIMAL(4,1) NULL COMMENT '°C',
    max_temp      DECIMAL(4,1) NULL COMMENT '°C',
    precipitation DECIMAL(5,1) NULL COMMENT 'mm',
    PRIMARY KEY (city_id, day),
    CONSTRAINT fk_daily_weather_city FOREIGN KEY (city_id) REFERENCES cities (id)
);

INSERT INTO daily_weather
SELECT mn.city_id, STR_TO_DATE(mn.date, '%Y%m%d'),
       NULLIF(mn.min_temp, -999), NULLIF(mx.max_temp, -999), NULLIF(p.precipitation, -999)
FROM minimum_temperatures mn
JOIN maximum_temperatures mx ON mx.city_id = mn.city_id AND mx.date = mn.date
LEFT JOIN precipitation p    ON p.city_id  = mn.city_id AND p.date  = mn.date;
```

## 7. Régió külön táblában

Az `enum` miatt új régióhoz sémamódosítás kell, és az `ORDER BY region` a felsorolás
sorrendjét adja, nem az ábécét. A `utf8mb4_hungarian_ci` összevetés miatt a
`WHERE region = 'dunantul'` is talál; egy azonosítónál ilyen kétértelműség nincs.

```sql
CREATE TABLE regions (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(100) NOT NULL UNIQUE);
ALTER TABLE cities ADD COLUMN region_id INT NULL,
    ADD CONSTRAINT fk_cities_region FOREIGN KEY (region_id) REFERENCES regions (id);
```

## 8. Adatminőségi megkötések

Egy sorban a minimum nagyobb a maximumnál (Túrkeve, 1922-12-20: 2,5 °C és 0,4 °C). Más
kiugró érték nincs. A sort a forrással kell összevetni; addig az API változatlanul adja
vissza.

```sql
ALTER TABLE daily_weather
    ADD CONSTRAINT chk_temp_order CHECK (min_temp IS NULL OR max_temp IS NULL OR min_temp <= max_temp),
    ADD CONSTRAINT chk_precipitation CHECK (precipitation >= 0);
```

## 9. Import scriptek

- A `003_import_weather_data.py` szöveg cserével rakja össze az SQL-t, és shellen futtatja:
  egy idézőjel vagy `$` a fájlnévben elrontja. Jobb egy MySQL klienskönyvtár, shell nélkül.
- A `001` és a `002` migráció dupla idézőjeles szövegeket használ (`"Budapest"`);
  `ANSI_QUOTES` módban ezek oszlopnévnek számítanak. Helyesen: `'Budapest'`.
- Sikertelen CSV import után csak egy `[ERROR]` sor jelenik meg, az inicializálás
  folytatódik, így hiányos adatbázissal indulhat a rendszer.
- Az első indításkor az import idejére a `db` healthcheck (10 × 5 s) kevés lehet; ezért a
  `docker-compose.yaml`-ban a `start_period`-ot megnöveltem.
