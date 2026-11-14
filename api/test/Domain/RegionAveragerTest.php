<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Domain;

use ADS\TakeHome\Domain\DailyWeather;
use ADS\TakeHome\Domain\RegionAverager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RegionAveragerTest extends TestCase
{
    #[Test]
    public function averagesEachValuePerDay(): void
    {
        $days = RegionAverager::average([
            1 => [new DailyWeather("2023-07-01", 14.8, 25.9, 0.0)],
            2 => [new DailyWeather("2023-07-01", 16.4, 27.6, 1.3)],
            3 => [new DailyWeather("2023-07-01", 18.6, 25.8, 0.0)],
            4 => [new DailyWeather("2023-07-01", 15.8, 25.3, 0.0)],
        ]);

        // Valódi adat: Dunántúl, 2023-07-01. 104.6 / 4 = 26.15 → 26.2; 1.3 / 4 = 0.325 → 0.3.
        $this->assertEquals([new DailyWeather("2023-07-01", 16.4, 26.2, 0.3)], $days);
        $this->assertSame(21.3, $days[0]->avgTemp());
    }

    #[Test]
    public function averagesOnlyCitiesWithMeasurement(): void
    {
        $days = RegionAverager::average([
            1 => [new DailyWeather("2023-01-01", null, 4.0, null)],
            2 => [new DailyWeather("2023-01-01", 1.0, 6.0, 2.0)],
            3 => [new DailyWeather("2023-01-01", 3.0, null, null)],
        ]);

        $this->assertEquals([new DailyWeather("2023-01-01", 2.0, 5.0, 2.0)], $days);
    }

    #[Test]
    public function valueIsNullWhenNoCityMeasuredIt(): void
    {
        $days = RegionAverager::average([
            1 => [new DailyWeather("2023-01-01", null, 4.0, null)],
            2 => [new DailyWeather("2023-01-01", null, 6.0, null)],
        ]);

        $this->assertEquals([new DailyWeather("2023-01-01", null, 5.0, null)], $days);
        $this->assertNull($days[0]->avgTemp());
    }

    #[Test]
    public function averageRoundsHalfAwayFromZeroDespiteFloatingPointError(): void
    {
        // Valódi adat: Dunántúl, 2004-03-13, minimum. Lebegőpontosan
        // (1.0 + 0.0 + 0.4 - 1.2) / 4 = 0.0499999…, ami 0.0-ra kerekedne; a helyes érték 0.05 → 0.1.
        $days = RegionAverager::average([
            1 => [new DailyWeather("2004-03-13", 1.0, null, null)],
            2 => [new DailyWeather("2004-03-13", 0.0, null, null)],
            3 => [new DailyWeather("2004-03-13", 0.4, null, null)],
            4 => [new DailyWeather("2004-03-13", -1.2, null, null)],
        ]);

        $this->assertSame(0.1, $days[0]->minTemp);
    }

    #[Test]
    public function averageIsNeverNegativeZero(): void
    {
        // (0.3 - 0.1 - 0.2) / 3 a lebegőpontos ábrázolás miatt -9e-18, kerekítve -0.0 lenne.
        $days = RegionAverager::average([
            1 => [new DailyWeather("2023-01-01", 0.3, 1.0, null)],
            2 => [new DailyWeather("2023-01-01", -0.1, 1.0, null)],
            3 => [new DailyWeather("2023-01-01", -0.2, 1.0, null)],
        ]);

        $this->assertSame("0", json_encode($days[0]->minTemp));
    }

    #[Test]
    public function returnsDaysInDateOrder(): void
    {
        $days = RegionAverager::average([
            1 => [
                new DailyWeather("2023-01-01", 1.0, 2.0, 0.0),
                new DailyWeather("2023-01-02", 3.0, 4.0, 0.0),
            ],
            2 => [
                new DailyWeather("2023-01-02", 5.0, 6.0, 0.0),
                new DailyWeather("2022-12-31", 7.0, 8.0, 0.0),
            ],
        ]);

        $this->assertSame(["2022-12-31", "2023-01-01", "2023-01-02"], array_map(fn (DailyWeather $day) => $day->date, $days));
        $this->assertSame(4.0, $days[2]->minTemp);
    }

    #[Test]
    public function singleCityIsUnchanged(): void
    {
        $day = new DailyWeather("2023-01-01", -3.4, 2.1, 0.7);

        $this->assertEquals([$day], RegionAverager::average([1 => [$day]]));
    }

    #[Test]
    public function noCitiesGiveNoDays(): void
    {
        $this->assertSame([], RegionAverager::average([]));
    }
}
