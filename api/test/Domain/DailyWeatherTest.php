<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Domain;

use ADS\TakeHome\Domain\DailyWeather;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DailyWeatherTest extends TestCase
{
    #[Test]
    public function avgTempIsMeanOfMinAndMax(): void
    {
        $this->assertSame(4.8, (new DailyWeather("2023-01-01", 1.2, 8.4, 0.0))->avgTemp());
    }

    #[Test]
    public function avgTempIsRoundedToOneDecimal(): void
    {
        // (1.2 + 8.5) / 2 = 4.85 → 4.9; negatív oldalon a nullától távolodva.
        $this->assertSame(4.9, (new DailyWeather("2023-01-01", 1.2, 8.5, null))->avgTemp());
        $this->assertSame(-4.9, (new DailyWeather("2023-01-01", -8.5, -1.2, null))->avgTemp());
    }

    #[Test]
    public function avgTempIsNullWhenMinOrMaxIsMissing(): void
    {
        $this->assertNull((new DailyWeather("2023-01-01", null, 8.4, 0.0))->avgTemp());
        $this->assertNull((new DailyWeather("2023-01-01", 1.2, null, 0.0))->avgTemp());
    }
}
