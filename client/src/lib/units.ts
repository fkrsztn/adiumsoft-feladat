/**
 * Mértékegységek és átváltás.
 *
 * Az API mindig SI egységben (°C, mm) adja az értékeket; a kliens csak a
 * megjelenítéskor vált. Mivel mindkét átváltás lineáris (és növekvő), a
 * medián és a módusz átváltva is helyes: elég az eredményt átváltani, nem
 * kell újraszámolni.
 */

export type UnitSystem = "metric" | "imperial";

export type MeasurementKind = "temperature" | "precipitation";

export type Unit = {
  /** A kiírt mértékegység, pl. „°C”. */
  symbol: string;
  /** Ennyi tizedesjeggyel írjuk ki a mért értékeket (és a móduszt). */
  decimals: number;
  /**
   * A medián legfeljebb ennyi tizedesjegyű. SI egységben eggyel több, mint a
   * mért értékeké, mert páros elemszámnál két érték átlaga (pl. 2,15 °C);
   * átváltás után a további tizedes csak zaj volna (pl. 62,78 °F).
   */
  medianDecimals: number;
  /** SI értékből ebbe az egységbe vált. */
  fromSi: (value: number) => number;
};

export const MM_PER_INCH = 25.4;

export function celsiusToFahrenheit(celsius: number): number {
  return (celsius * 9) / 5 + 32;
}

export function millimetersToInches(millimeters: number): number {
  return millimeters / MM_PER_INCH;
}

const UNITS: Record<UnitSystem, Record<MeasurementKind, Unit>> = {
  metric: {
    temperature: {
      symbol: "°C",
      decimals: 1,
      medianDecimals: 2,
      fromSi: (value) => value,
    },
    precipitation: {
      symbol: "mm",
      decimals: 1,
      medianDecimals: 2,
      fromSi: (value) => value,
    },
  },
  imperial: {
    temperature: {
      symbol: "°F",
      decimals: 1,
      medianDecimals: 1,
      fromSi: celsiusToFahrenheit,
    },
    // Az inch 25,4-szer nagyobb egység: egy tizedes (2,5 mm-es lépés)
    // túl durva lenne, ezért két tizedessel írjuk ki.
    precipitation: {
      symbol: "in",
      decimals: 2,
      medianDecimals: 2,
      fromSi: millimetersToInches,
    },
  },
};

export function unitFor(system: UnitSystem, kind: MeasurementKind): Unit {
  return UNITS[system][kind];
}

export const UNIT_SYSTEM_LABELS: Record<UnitSystem, string> = {
  metric: "°C, mm",
  imperial: "°F, in",
};
