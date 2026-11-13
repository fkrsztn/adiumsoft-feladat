/**
 * Az időszak szabályai. A szerver ugyanezeket ellenőrzi
 * (`api/src/Domain/DateRange.php`); a kliens azért is ellenőriz, hogy a
 * felhasználó azonnal, kérés nélkül kapjon visszajelzést.
 */

/** Az adatbázisban tárolt mérések első napja. */
export const FIRST_AVAILABLE_DATE = "1901-01-01";

/** Az adatbázisban tárolt mérések utolsó napja. */
export const LAST_AVAILABLE_DATE = "2023-12-31";

/** Egy lekérdezés legfeljebb ennyi napot ölelhet fel (egy szökőév). */
export const MAX_DAYS = 366;

const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;
const MS_PER_DAY = 24 * 60 * 60 * 1000;

/**
 * Ellenőrzi az időszakot. Hibánál a felhasználónak szóló üzenetet adja
 * vissza, egyébként `null`-t.
 */
export function validateDateRange(
  startDate: string,
  endDate: string,
): string | null {
  if (startDate === "" || endDate === "") {
    return "Add meg a kezdő és a záró dátumot.";
  }
  const start = parseIsoDate(startDate);
  const end = parseIsoDate(endDate);
  if (start === null) {
    return "Érvénytelen kezdő dátum.";
  }
  if (end === null) {
    return "Érvénytelen záró dátum.";
  }
  if (start > end) {
    return "A kezdő dátum nem lehet későbbi a záró dátumnál.";
  }
  if (startDate < FIRST_AVAILABLE_DATE || endDate > LAST_AVAILABLE_DATE) {
    return `Adatok csak ${FIRST_AVAILABLE_DATE} és ${LAST_AVAILABLE_DATE} között érhetők el.`;
  }
  if (countDays(start, end) > MAX_DAYS) {
    return `Legfeljebb ${MAX_DAYS} napos időszak kérhető le.`;
  }
  return null;
}

/** Napok száma, a kezdő és a záró napot is beleszámolva. */
function countDays(start: number, end: number): number {
  return Math.round((end - start) / MS_PER_DAY) + 1;
}

/**
 * `ÉÉÉÉ-HH-NN` → időbélyeg (UTC éjfél), vagy `null`, ha a nap nem létezik
 * (pl. 2023-02-30).
 */
function parseIsoDate(value: string): number | null {
  if (!ISO_DATE.test(value)) {
    return null;
  }
  const [year, month, day] = value.split("-").map(Number);
  const time = Date.UTC(year, month - 1, day);
  const date = new Date(time);
  const exists =
    date.getUTCFullYear() === year &&
    date.getUTCMonth() === month - 1 &&
    date.getUTCDate() === day;
  return exists ? time : null;
}
