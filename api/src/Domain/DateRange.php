<?php

declare(strict_types=1);

namespace ADS\TakeHome\Domain;

use ADS\TakeHome\Exceptions\ValidationException;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Lekérdezhető időszak: két nap, mindkét végén zárt intervallum.
 *
 * Csak érvényes időszak hozható létre: a dátumok léteznek, a kezdő nem
 * későbbi a zárónál, mindkettő az elérhető adatok határain belül van, és az
 * időszak legfeljebb MAX_DAYS napos.
 */
final readonly class DateRange
{
    /** Az adatbázisban tárolt mérések első napja. */
    public const FIRST_AVAILABLE_DATE = "1901-01-01";

    /** Az adatbázisban tárolt mérések utolsó napja. */
    public const LAST_AVAILABLE_DATE = "2023-12-31";

    /**
     * Egy lekérdezés legfeljebb ennyi napot ölelhet fel (egy szökőév).
     * Napi bontásban ennél hosszabb időszak táblázatban már nem áttekinthető.
     */
    public const MAX_DAYS = 366;

    private const ISO_FORMAT = "Y-m-d";

    private function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
    }

    /**
     * @param string $start Kezdő nap, `ÉÉÉÉ-HH-NN` formátumban.
     * @param string $end Záró nap, `ÉÉÉÉ-HH-NN` formátumban.
     *
     * @throws ValidationException ha az időszak érvénytelen.
     */
    public static function fromIsoStrings(string $start, string $end): self
    {
        $startDate = self::parseIsoDate($start, "kezdő dátum");
        $endDate = self::parseIsoDate($end, "záró dátum");

        if ($startDate > $endDate) {
            throw new ValidationException("A kezdő dátum nem lehet későbbi a záró dátumnál.");
        }

        $firstAvailable = self::parseIsoDate(self::FIRST_AVAILABLE_DATE, "első elérhető dátum");
        $lastAvailable = self::parseIsoDate(self::LAST_AVAILABLE_DATE, "utolsó elérhető dátum");
        if ($startDate < $firstAvailable || $endDate > $lastAvailable) {
            throw new ValidationException(sprintf(
                "Adatok csak %s és %s között érhetők el.",
                self::FIRST_AVAILABLE_DATE,
                self::LAST_AVAILABLE_DATE,
            ));
        }

        $range = new self($startDate, $endDate);
        if ($range->dayCount() > self::MAX_DAYS) {
            throw new ValidationException(sprintf("Legfeljebb %d napos időszak kérhető le.", self::MAX_DAYS));
        }

        return $range;
    }

    /**
     * Az időszak napjainak száma, a kezdő és a záró napot is beleszámolva.
     */
    public function dayCount(): int
    {
        // UTC-ben számolunk, így az óraátállítás nem zavar be.
        return (int) $this->start->diff($this->end)->days + 1;
    }

    public function startIso(): string
    {
        return $this->start->format(self::ISO_FORMAT);
    }

    public function endIso(): string
    {
        return $this->end->format(self::ISO_FORMAT);
    }

    /**
     * A kezdő nap az adatbázis formátumában (`ÉÉÉÉHHNN` egész szám, pl. 20230115).
     */
    public function startAsDbDate(): int
    {
        return (int) $this->start->format("Ymd");
    }

    /**
     * A záró nap az adatbázis formátumában (`ÉÉÉÉHHNN` egész szám, pl. 20230115).
     */
    public function endAsDbDate(): int
    {
        return (int) $this->end->format("Ymd");
    }

    private static function parseIsoDate(string $value, string $label): DateTimeImmutable
    {
        // A "!" a nem megadott részeket (idő) nullázza, így a napok összevethetők.
        $date = DateTimeImmutable::createFromFormat("!" . self::ISO_FORMAT, $value, new DateTimeZone("UTC"));

        // A visszaalakítás kiszűri a nem létező napokat (a PHP a 2023-02-30-at
        // csendben március 2-re görgetné) és a laza alakokat (pl. 2023-1-5).
        if ($date === false || $date->format(self::ISO_FORMAT) !== $value) {
            throw new ValidationException("Érvénytelen {$label}: ÉÉÉÉ-HH-NN formátumú, létező napot vár.");
        }
        return $date;
    }
}
