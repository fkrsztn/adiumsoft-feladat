<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests;

use ADS\TakeHome\Api;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Tests\Support\FakeDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * A végpontok tesztje a teljes rétegrenden át (Router → Controller → Service
 * → Repository), valódi adatbázis helyett FakeDatabase-zel.
 */
class ApiTest extends TestCase
{
    private const CITY_ROWS = [
        ["id" => 1, "name" => "Budapest", "region" => "Közép-Magyarország"],
        ["id" => 9, "name" => "Szombathely", "region" => "Dunántúl"],
        ["id" => 10, "name" => "Szombathely", "region" => "Észak és Alföld"],
        ["id" => 6, "name" => "Pécs", "region" => "Dunántúl"],
    ];

    private const VALID_QUERY = [
        "cityId" => "10",
        "startDate" => "2023-01-01",
        "endDate" => "2023-01-03",
    ];

    private const VALID_REGION_QUERY = [
        "region" => "Dunántúl",
        "startDate" => "2023-01-01",
        "endDate" => "2023-01-03",
    ];

    private FakeDatabase $db;

    protected function setUp(): void
    {
        $this->db = new FakeDatabase();
    }

    #[Test]
    public function listsCitiesSortedWithCorrectedName(): void
    {
        $this->db->willReturn("FROM cities", self::CITY_ROWS);

        $body = $this->get("/cities", [], expectedStatus: 200);

        $this->assertSame([
            "cities" => [
                ["id" => 1, "name" => "Budapest", "region" => "Közép-Magyarország"],
                ["id" => 6, "name" => "Pécs", "region" => "Dunántúl"],
                ["id" => 9, "name" => "Szombathely", "region" => "Dunántúl"],
                ["id" => 10, "name" => "Túrkeve", "region" => "Észak és Alföld"],
            ],
        ], $body);
    }

    #[Test]
    public function listsRegionsInHungarianAlphabeticalOrder(): void
    {
        // Az enum sorrendje (és az SQL ORDER BY) Közép-Magyarország, Észak és Alföld lenne.
        $this->db->willReturn("DISTINCT region", [
            ["region" => "Dunántúl"],
            ["region" => "Közép-Magyarország"],
            ["region" => "Észak és Alföld"],
        ]);

        $body = $this->get("/regions", [], expectedStatus: 200);

        $this->assertSame(["regions" => ["Dunántúl", "Észak és Alföld", "Közép-Magyarország"]], $body);
    }

    #[Test]
    public function returnsCityWeather(): void
    {
        $this->db
            ->willReturn("WHERE id = :cityId", [self::CITY_ROWS[2]])
            ->willReturn("FROM minimum_temperatures", [
                ["cityId" => 10, "date" => 20230101, "minTemp" => 1.2, "maxTemp" => 8.4, "precipitation" => 0.0],
                ["cityId" => 10, "date" => 20230102, "minTemp" => -0.5, "maxTemp" => 3.0, "precipitation" => -999.0],
                ["cityId" => 10, "date" => 20230103, "minTemp" => -999.0, "maxTemp" => -999.0, "precipitation" => null],
            ]);

        $body = $this->get("/weather/city", self::VALID_QUERY, expectedStatus: 200);

        $this->assertSame([
            "city" => ["id" => 10, "name" => "Túrkeve", "region" => "Észak és Alföld"],
            "startDate" => "2023-01-01",
            "endDate" => "2023-01-03",
            "units" => ["temperature" => "°C", "precipitation" => "mm"],
            // A JSON a kerek számokat tizedesjegy nélkül írja ki (0.0 → 0), a
            // dekódolás után ezért egész számként jönnek vissza.
            "days" => [
                ["date" => "2023-01-01", "minTemp" => 1.2, "maxTemp" => 8.4, "avgTemp" => 4.8, "precipitation" => 0],
                // A -999 (hiányzó mérés) és a hiányzó sor is null.
                ["date" => "2023-01-02", "minTemp" => -0.5, "maxTemp" => 3, "avgTemp" => 1.3, "precipitation" => null],
                ["date" => "2023-01-03", "minTemp" => null, "maxTemp" => null, "avgTemp" => null, "precipitation" => null],
            ],
            // A hiányzó értékek a statisztikából kimaradnak.
            "summary" => [
                "minTemp" => ["median" => 0.35, "mode" => ["values" => [], "count" => 0]],
                "maxTemp" => ["median" => 5.7, "mode" => ["values" => [], "count" => 0]],
                "avgTemp" => ["median" => 3.05, "mode" => ["values" => [], "count" => 0]],
                "precipitation" => ["median" => 0, "mode" => ["values" => [], "count" => 0]],
            ],
        ], $body);
    }

    #[Test]
    public function passesCityAndDatesAsQueryParameters(): void
    {
        $this->db->willReturn("WHERE id = :cityId", [self::CITY_ROWS[2]]);

        $this->get("/weather/city", self::VALID_QUERY, expectedStatus: 200);

        $this->assertSame([":cityId" => 10], $this->db->queries[0]["params"]);
        $this->assertSame(
            [":startDate" => 20230101, ":endDate" => 20230103, ":city0" => 10],
            $this->db->queries[1]["params"],
        );
    }

    #[Test]
    public function roundsFloatStorageNoise(): void
    {
        $this->db
            ->willReturn("WHERE id = :cityId", [self::CITY_ROWS[2]])
            ->willReturn("FROM minimum_temperatures", [
                ["cityId" => 10, "date" => 20230101, "minTemp" => 0.100000001490116, "maxTemp" => 33.70000076293945, "precipitation" => 2.299999952316284],
            ]);

        $body = $this->get("/weather/city", self::VALID_QUERY, expectedStatus: 200);

        $this->assertSame(
            ["date" => "2023-01-01", "minTemp" => 0.1, "maxTemp" => 33.7, "avgTemp" => 16.9, "precipitation" => 2.3],
            $body["days"][0],
        );
    }

    #[Test]
    public function returnsEmptyDaysWhenNoData(): void
    {
        $this->db->willReturn("WHERE id = :cityId", [self::CITY_ROWS[0]]);

        $body = $this->get("/weather/city", self::VALID_QUERY, expectedStatus: 200);

        $this->assertSame([], $body["days"]);
    }

    #[Test]
    public function returnsSummaryWithModeOfMeasuredValues(): void
    {
        $this->db
            ->willReturn("WHERE id = :cityId", [self::CITY_ROWS[2]])
            ->willReturn("FROM minimum_temperatures", [
                ["cityId" => 10, "date" => 20230101, "minTemp" => 2.0, "maxTemp" => 6.0, "precipitation" => 0.0],
                ["cityId" => 10, "date" => 20230102, "minTemp" => 2.0, "maxTemp" => 4.0, "precipitation" => 0.0],
                ["cityId" => 10, "date" => 20230103, "minTemp" => 3.0, "maxTemp" => 4.0, "precipitation" => 1.5],
            ]);

        $body = $this->get("/weather/city", self::VALID_QUERY, expectedStatus: 200);

        $this->assertSame([
            "minTemp" => ["median" => 2, "mode" => ["values" => [2], "count" => 2]],
            "maxTemp" => ["median" => 4, "mode" => ["values" => [4], "count" => 2]],
            // Átlagok: 4.0, 3.0, 3.5 – mind különböző, nincs módusz.
            "avgTemp" => ["median" => 3.5, "mode" => ["values" => [], "count" => 0]],
            "precipitation" => ["median" => 0, "mode" => ["values" => [0], "count" => 2]],
        ], $body["summary"]);
    }

    #[Test]
    public function returns404ForUnknownCity(): void
    {
        $body = $this->get("/weather/city", ["cityId" => "99"] + self::VALID_QUERY, expectedStatus: 404);

        $this->assertSame(["error" => "Nincs 99 azonosítójú város."], $body);
    }

    #[Test]
    public function returnsRegionWeatherAveragedOverItsCities(): void
    {
        $this->db
            ->willReturn("DISTINCT region", [["region" => "Dunántúl"], ["region" => "Észak és Alföld"]])
            ->willReturn("WHERE region = :region", [self::CITY_ROWS[1], self::CITY_ROWS[3]])
            ->willReturn("FROM minimum_temperatures", [
                ["cityId" => 6, "date" => 20230101, "minTemp" => 2.0, "maxTemp" => 9.0, "precipitation" => 1.0],
                ["cityId" => 6, "date" => 20230102, "minTemp" => 1.0, "maxTemp" => 5.0, "precipitation" => -999.0],
                ["cityId" => 9, "date" => 20230101, "minTemp" => 0.0, "maxTemp" => 6.0, "precipitation" => 0.0],
                ["cityId" => 9, "date" => 20230102, "minTemp" => -999.0, "maxTemp" => 3.0, "precipitation" => -999.0],
            ]);

        $body = $this->get("/weather/region", self::VALID_REGION_QUERY, expectedStatus: 200);

        $this->assertSame([
            "name" => "Dunántúl",
            "cities" => [["id" => 6, "name" => "Pécs"], ["id" => 9, "name" => "Szombathely"]],
        ], $body["region"]);
        $this->assertSame([
            ["date" => "2023-01-01", "minTemp" => 1, "maxTemp" => 7.5, "avgTemp" => 4.3, "precipitation" => 0.5],
            // Szombathely minimuma hiányzik → csak Pécs; csapadék egyiknél sincs → null.
            ["date" => "2023-01-02", "minTemp" => 1, "maxTemp" => 4, "avgTemp" => 2.5, "precipitation" => null],
        ], $body["days"]);
        $this->assertSame(["median" => 1, "mode" => ["values" => [1], "count" => 2]], $body["summary"]["minTemp"]);
        $this->assertSame(["median" => 0.5, "mode" => ["values" => [], "count" => 0]], $body["summary"]["precipitation"]);
    }

    #[Test]
    public function queriesWeatherForAllCitiesOfTheRegion(): void
    {
        $this->db
            ->willReturn("DISTINCT region", [["region" => "Dunántúl"]])
            ->willReturn("WHERE region = :region", [self::CITY_ROWS[1], self::CITY_ROWS[3]]);

        $this->get("/weather/region", self::VALID_REGION_QUERY, expectedStatus: 200);

        $this->assertSame([":region" => "Dunántúl"], $this->db->queries[1]["params"]);
        $this->assertSame(
            [":startDate" => 20230101, ":endDate" => 20230103, ":city0" => 6, ":city1" => 9],
            $this->db->queries[2]["params"],
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unknownRegionProvider(): array
    {
        return [
            "nem létező régió" => ["Délvidék"],
            // Az adatbázis összevetése ékezet- és kisbetű-érzéketlen; az API pontos nevet vár.
            "ékezet és nagybetű nélkül" => ["dunantul"],
        ];
    }

    #[Test]
    #[DataProvider("unknownRegionProvider")]
    public function returns404ForUnknownRegion(string $region): void
    {
        $this->db->willReturn("DISTINCT region", [["region" => "Dunántúl"]]);

        $body = $this->get("/weather/region", ["region" => $region] + self::VALID_REGION_QUERY, expectedStatus: 404);

        $this->assertSame(["error" => "Nincs „{$region}” nevű régió."], $body);
    }

    #[Test]
    public function returns400ForMissingRegion(): void
    {
        $body = $this->get("/weather/region", array_diff_key(self::VALID_REGION_QUERY, ["region" => true]), expectedStatus: 400);

        $this->assertSame(["error" => "Hiányzó paraméter: region."], $body);
        $this->assertSame([], $this->db->queries);
    }

    /**
     * @return array<string, array{array<string, string|list<string>>, string}>
     */
    public static function invalidQueryProvider(): array
    {
        $valid = self::VALID_QUERY;
        return [
            "hiányzó cityId" => [
                array_diff_key($valid, ["cityId" => true]),
                "Hiányzó paraméter: cityId.",
            ],
            "üres cityId" => [
                ["cityId" => ""] + $valid,
                "Hiányzó paraméter: cityId.",
            ],
            "nem szám cityId" => [
                ["cityId" => "abc"] + $valid,
                "Érvénytelen paraméter: cityId (pozitív egész számot vár).",
            ],
            "nulla cityId" => [
                ["cityId" => "0"] + $valid,
                "Érvénytelen paraméter: cityId (pozitív egész számot vár).",
            ],
            "tört cityId" => [
                ["cityId" => "1.5"] + $valid,
                "Érvénytelen paraméter: cityId (pozitív egész számot vár).",
            ],
            "tömb cityId (?cityId[]=1)" => [
                ["cityId" => ["1"]] + $valid,
                "Érvénytelen paraméter: cityId (csak egy érték adható meg).",
            ],
            "hiányzó startDate" => [
                array_diff_key($valid, ["startDate" => true]),
                "Hiányzó paraméter: startDate.",
            ],
            "hiányzó endDate" => [
                array_diff_key($valid, ["endDate" => true]),
                "Hiányzó paraméter: endDate.",
            ],
            "rossz dátum" => [
                ["startDate" => "2023-02-30"] + $valid,
                "Érvénytelen kezdő dátum: ÉÉÉÉ-HH-NN formátumú, létező napot vár.",
            ],
            "túl hosszú időszak" => [
                ["startDate" => "2022-01-01"] + $valid,
                "Legfeljebb 366 napos időszak kérhető le.",
            ],
        ];
    }

    /**
     * @param array<string, string|list<string>> $query
     */
    #[Test]
    #[DataProvider("invalidQueryProvider")]
    public function returns400ForInvalidQuery(array $query, string $expectedError): void
    {
        $body = $this->get("/weather/city", $query, expectedStatus: 400);

        $this->assertSame(["error" => $expectedError], $body);
        $this->assertSame([], $this->db->queries, "Hibás bemenetnél nem fordulhat az adatbázishoz.");
    }

    /**
     * @param array<string, string|list<string>> $query
     *
     * @return array<mixed>
     */
    private function get(string $path, array $query, int $expectedStatus): array
    {
        $router = Api::createRouter($this->db);
        $res = $router->dispatch("GET", $path, new HttpRequest($query));

        $this->assertSame($expectedStatus, $res->getStatus(), (string) $res->getBody());
        return $this->decodeBody($res);
    }

    /**
     * @return array<mixed>
     */
    private function decodeBody(HttpResponseInterface $res): array
    {
        $decoded = json_decode((string) $res->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        return $decoded;
    }
}
