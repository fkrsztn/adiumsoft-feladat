<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Domain;

use ADS\TakeHome\Domain\Mode;
use ADS\TakeHome\Domain\Statistics;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class StatisticsTest extends TestCase
{
    #[Test]
    public function medianOfOddCountIsMiddleValue(): void
    {
        $this->assertSame(3.2, Statistics::median([5.0, 1.1, 3.2]));
    }

    #[Test]
    public function medianOfEvenCountIsAverageOfTwoMiddleValues(): void
    {
        $this->assertSame(2.15, Statistics::median([4.0, 1.0, 2.0, 2.3]));
    }

    #[Test]
    public function medianOfSingleValue(): void
    {
        $this->assertSame(-7.5, Statistics::median([-7.5]));
    }

    #[Test]
    public function medianOfEmptyListIsNull(): void
    {
        $this->assertNull(Statistics::median([]));
    }

    #[Test]
    public function medianRemovesFloatingPointNoise(): void
    {
        // 0.1 + 0.2 = 0.30000000000000004 a lebegőpontos ábrázolás miatt.
        $this->assertSame(0.15, Statistics::median([0.1, 0.2]));
    }

    #[Test]
    public function modeWithSingleMostFrequentValue(): void
    {
        $this->assertEquals(new Mode([2.5], 3), Statistics::mode([2.5, 1.0, 2.5, 3.0, 2.5, 1.0]));
    }

    #[Test]
    public function modeReturnsAllTiedValuesInAscendingOrder(): void
    {
        $this->assertEquals(new Mode([-1.2, 0.0, 4.4], 2), Statistics::mode([4.4, 0.0, -1.2, 4.4, -1.2, 0.0, 7.0]));
    }

    #[Test]
    public function noModeWhenEveryValueOccursOnce(): void
    {
        $this->assertEquals(Mode::none(), Statistics::mode([1.0, 2.0, 3.0]));
    }

    #[Test]
    public function noModeForEmptyList(): void
    {
        $this->assertEquals(Mode::none(), Statistics::mode([]));
    }

    #[Test]
    public function modeComparesValuesRoundedToOneDecimal(): void
    {
        // 1.24 és 1.21 egyaránt 1.2 – a forrásadat pontossága egy tizedes.
        $this->assertEquals(new Mode([1.2], 2), Statistics::mode([1.24, 1.21, 5.0]));
    }

    #[Test]
    public function modeTreatsNegativeZeroAsZero(): void
    {
        $this->assertEquals(new Mode([0.0], 2), Statistics::mode([-0.04, 0.0]));
    }

    #[Test]
    public function summarizeSkipsMissingValues(): void
    {
        $summary = Statistics::summarize([3.0, null, 1.0, 3.0, null]);

        $this->assertSame(3.0, $summary->median);
        $this->assertEquals(new Mode([3.0], 2), $summary->mode);
    }

    #[Test]
    public function summarizeOfOnlyMissingValues(): void
    {
        $summary = Statistics::summarize([null, null]);

        $this->assertNull($summary->median);
        $this->assertEquals(Mode::none(), $summary->mode);
    }
}
