<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Domain;

use ADS\TakeHome\Domain\CityNameCorrections;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CityNameCorrectionsTest extends TestCase
{
    #[Test]
    public function renamesCity10ToTurkeve(): void
    {
        $this->assertSame("Túrkeve", CityNameCorrections::apply(10, "Szombathely"));
    }

    #[Test]
    public function keepsOtherNamesUnchanged(): void
    {
        $this->assertSame("Szombathely", CityNameCorrections::apply(9, "Szombathely"));
        $this->assertSame("Budapest", CityNameCorrections::apply(1, "Budapest"));
    }
}
