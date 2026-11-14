# API

PHP 8.3 REST API a meteorológiai adatokhoz. Minden válasz JSON, a hibák is.

Fejlesztői cím: `http://localhost:8080`, prodban: `http://localhost:8300/api/v1`.

## Végpontok

| Végpont | Mit ad vissza |
|---|---|
| `GET /cities` | A városok listája (azonosító, név, régió). |
| `GET /regions` | A régiók nevei. |
| `GET /weather/city?cityId=1&startDate=2023-01-01&endDate=2023-01-31` | Egy város napi adatai. |
| `GET /weather/region?region=Dunántúl&startDate=2023-01-01&endDate=2023-01-31` | Egy régió napi adatai, a városok átlagaként. |

A dátumok formátuma `ÉÉÉÉ-HH-NN`, az időszak mindkét vége beleszámít.

Példaválasz (`/weather/region`, rövidítve):

```json
{
  "region": { "name": "Dunántúl", "cities": [{ "id": 3, "name": "Keszthely" }, "…"] },
  "startDate": "2023-07-01",
  "endDate": "2023-07-02",
  "units": { "temperature": "°C", "precipitation": "mm" },
  "days": [
    { "date": "2023-07-01", "minTemp": 16.4, "maxTemp": 26.2, "avgTemp": 21.3, "precipitation": 0.3 }
  ],
  "summary": {
    "minTemp": { "median": 16.55, "mode": { "values": [], "count": 0 } },
    "…": "ugyanígy a maxTemp, avgTemp és precipitation oszlopra"
  }
}
```

A `/weather/city` válasza ugyanilyen, csak a `region` helyett `city` mező van benne.

## Hibák

| Kód | Mikor |
|---|---|
| 400 | Hibás vagy hiányzó paraméter, rossz időszak. |
| 404 | Nem létező város, régió vagy útvonal. |
| 405 | Nem `GET` kérés. |
| 500 | Szerveroldali hiba. Prodban csak általános üzenet, a részletek a naplóba kerülnek. |

A hibaválasz formája: `{"error": "…"}`, magyar üzenettel.

## Számítási szabályok

- **Átlaghőmérséklet:** (minimum + maximum) / 2, egy tizedesre kerekítve. Ha bármelyik hiányzik, az átlag `null`.
- **Régió:** naponta a régió városainak átlaga, külön a minimumra, a maximumra és a csapadékra. Csak azok a városok számítanak, ahol aznap volt mérés. Az átlaghőmérséklet a régió minimumából és maximumából számolódik, így a táblázat minden sora önmagában ellenőrizhető.
- **Medián és módusz:** oszloponként, a hiányzó értékek nélkül. Holtversenynél a módusz minden leggyakoribb értéket visszaad. Ha minden érték csak egyszer fordul elő, nincs módusz (`values` üres).
- **Hiányzó adat:** az adatbázis `-999` értékkel jelöli (a hőmérsékletben is), ezt az API `null`-ra cseréli.

## Döntések, amelyek eltérhetnek a várttól

- **Egy lekérdezés legfeljebb 366 napot ölel fel.** A válasz napi bontású: a teljes időszak (1901–2023) városonként 45 ezer sor lenne, ami egy táblázatban nem áttekinthető, és lapozás nélkül feleslegesen nagy válasz. A határ egyetlen konstans (`src/Domain/DateRange.php`, `MAX_DAYS`).
- **A 10-es város neve Túrkeve, nem Szombathely.** Az adatbázisban két Szombathely szerepel, de a 10-es azonosítóhoz Túrkeve mérései tartoznak (az import script és a régió is ezt mutatja). Az API javítja a nevet (`src/Domain/CityNameCorrections.php`); a helyes javítás az adatbázisban volna, lásd `db-suggestions.md`.
- **A mintavégpontok (`/hello`, `/budapest`) és a hozzájuk tartozó kód törölve** (`ExampleController`, `ExampleTest`, a kliensen a `Greeter`). Csak a keretrendszer bemutatására szolgáltak, a megoldáshoz nem kellenek, a helyükre valódi végpontok és tesztek kerültek. A gyökér README `curl localhost:8080/hello?name=vendég` ellenőrzése helyett: `curl localhost:8080/cities`.
- **A keretrendszer módosult** (`src/Framework/`): a `MySQL` osztály valódi prepared statementet használ (az eredeti szövegcserével illesztette be az értékeket), a `Router` minden hibát elkap, és csak fejlesztői módban (`APP_DEBUG`) ad részletes hibaüzenetet. Az új `DatabaseInterface` miatt az API adatbázis nélkül is tesztelhető.
- **`composer.json`, `config.platform`:** friss buildnél a konténerben az `ext-iconv` kiterjesztés nem töltődik be, és emiatt a `composer install` leállt. A platform-beállítás ezt kerüli meg; a kiterjesztést csak egy polyfill kéri, amely itt nem fut le.

## Felépítés

```
src/
  Api.php            végpontok regisztrálása
  Controllers/       paraméterek ellenőrzése, JSON válasz
  Services/          a lekérdezések összeállítása
  Repositories/      SQL lekérdezések
  Domain/            számítások (átlag, medián, módusz, időszak, kerekítés)
  Framework/         a kapott keretrendszer (módosítva, lásd fent)
test/                PHPUnit tesztek, adatbázis nélkül
```

## Parancsok

A `takehome/` mappából, futó `api-dev` konténerrel:

```sh
docker exec takehome-api-dev vendor/bin/phpunit      # tesztek
docker exec takehome-api-dev composer analyse        # statikus elemzés (phpstan, 8-as szint)
docker exec takehome-api-dev composer format         # kódformázás (php-cs-fixer)
```
