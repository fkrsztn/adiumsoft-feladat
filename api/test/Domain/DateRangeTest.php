<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Domain;

use ADS\TakeHome\Domain\DateRange;
use ADS\TakeHome\Exceptions\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    #[Test]
    public function acceptsValidRange(): void
    {
        $range = DateRange::fromIsoStrings("2023-01-15", "2023-02-15");

        $this->assertSame("2023-01-15", $range->startIso());
        $this->assertSame("2023-02-15", $range->endIso());
        $this->assertSame(20230115, $range->startAsDbDate());
        $this->assertSame(20230215, $range->endAsDbDate());
        $this->assertSame(32, $range->dayCount());
    }

    #[Test]
    public function acceptsSingleDay(): void
    {
        $range = DateRange::fromIsoStrings("2023-06-01", "2023-06-01");

        $this->assertSame(1, $range->dayCount());
    }

    #[Test]
    public function acceptsWholeLeapYearAsMaximum(): void
    {
        $range = DateRange::fromIsoStrings("2020-01-01", "2020-12-31");

        $this->assertSame(DateRange::MAX_DAYS, $range->dayCount());
    }

    #[Test]
    public function acceptsFirstAndLastAvailableDays(): void
    {
        DateRange::fromIsoStrings(DateRange::FIRST_AVAILABLE_DATE, "1901-01-31");
        DateRange::fromIsoStrings("2023-12-01", DateRange::LAST_AVAILABLE_DATE);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function countsDaysCorrectlyAcrossDaylightSavingChange(): void
    {
        // 2023-03-26-án volt az óraátállítás.
        $range = DateRange::fromIsoStrings("2023-03-25", "2023-03-27");

        $this->assertSame(3, $range->dayCount());
    }

    #[Test]
    public function rejectsRangeLongerThanMaximum(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Legfeljebb 366 napos időszak kérhető le.");

        DateRange::fromIsoStrings("2022-01-01", "2023-01-02");
    }

    #[Test]
    public function rejectsStartAfterEnd(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("A kezdő dátum nem lehet későbbi a záró dátumnál.");

        DateRange::fromIsoStrings("2023-02-01", "2023-01-31");
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function outOfDataRangeProvider(): array
    {
        return [
            "az első elérhető nap előtt" => ["1900-12-31", "1901-01-05"],
            "az utolsó elérhető nap után" => ["2023-12-30", "2024-01-01"],
        ];
    }

    #[Test]
    #[DataProvider("outOfDataRangeProvider")]
    public function rejectsDatesOutsideAvailableData(string $start, string $end): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Adatok csak 1901-01-01 és 2023-12-31 között érhetők el.");

        DateRange::fromIsoStrings($start, $end);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidDateProvider(): array
    {
        return [
            "nem létező nap" => ["2023-02-30"],
            "február 29. nem szökőévben" => ["2023-02-29"],
            "kezdő nullák nélkül" => ["2023-1-5"],
            "más formátum" => ["2023.01.05"],
            "időponttal" => ["2023-01-05T10:00"],
            "szöveg" => ["tegnap"],
        ];
    }

    #[Test]
    #[DataProvider("invalidDateProvider")]
    public function rejectsInvalidDate(string $date): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Érvénytelen kezdő dátum");

        DateRange::fromIsoStrings($date, "2023-03-01");
    }

    #[Test]
    public function namesEndDateInErrorMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Érvénytelen záró dátum");

        DateRange::fromIsoStrings("2023-03-01", "2023-13-01");
    }
}
