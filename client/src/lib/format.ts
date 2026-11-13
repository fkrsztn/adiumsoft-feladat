import type { Mode } from "../api/types.ts";
import type { Unit } from "./units.ts";

/** Hiányzó mérés szövege a táblázatban. */
export const MISSING_VALUE_TEXT = "nincs adat";

/** Ennyi holtversenyes módusz értéket írunk ki, a többit „+N” jelzi. */
const MAX_LISTED_MODES = 3;

const numberFormats = new Map<string, Intl.NumberFormat>();

/**
 * Magyar számformátum adott tizedesjeggyel. A `signDisplay: "negative"`
 * miatt a -0 nem „-0,0”-ként jelenik meg (pl. -17,8 °C → -0,04 °F).
 */
function formatNumber(
  value: number,
  minDecimals: number,
  maxDecimals: number,
): string {
  const key = `${minDecimals}-${maxDecimals}`;
  let format = numberFormats.get(key);
  if (format === undefined) {
    format = new Intl.NumberFormat("hu-HU", {
      minimumFractionDigits: minDecimals,
      maximumFractionDigits: maxDecimals,
      signDisplay: "negative",
    });
    numberFormats.set(key, format);
  }
  return format.format(value);
}

/**
 * Mért érték (SI egységben) a választott mértékegységben, pl. „12,3 °C”;
 * hiányzó értékre „nincs adat”.
 */
export function formatMeasurement(value: number | null, unit: Unit): string {
  if (value === null) {
    return MISSING_VALUE_TEXT;
  }
  const converted = unit.fromSi(value);
  return `${formatNumber(converted, unit.decimals, unit.decimals)} ${unit.symbol}`;
}

/**
 * Medián, a mértékegység `medianDecimals` beállítása szerinti pontossággal
 * (pl. „2,15 °C”, de „35,9 °F”).
 */
export function formatMedian(value: number | null, unit: Unit): string {
  if (value === null) {
    return MISSING_VALUE_TEXT;
  }
  const converted = unit.fromSi(value);
  return `${formatNumber(converted, unit.decimals, unit.medianDecimals)} ${unit.symbol}`;
}

/**
 * Módusz, pl. „12,3 °C (3×)”, holtversenyben „12,3 / 14,1 °C (3×)”; sok
 * értéknél az első néhány után „+N”. Ha nincs módusz: „nincs”.
 */
export function formatMode(mode: Mode, unit: Unit): string {
  if (mode.values.length === 0) {
    return "nincs";
  }
  const listed = mode.values
    .slice(0, MAX_LISTED_MODES)
    .map((value) =>
      formatNumber(unit.fromSi(value), unit.decimals, unit.decimals),
    )
    .join(" / ");
  const hidden = mode.values.length - MAX_LISTED_MODES;
  const more = hidden > 0 ? ` +${hidden}` : "";
  return `${listed}${more} ${unit.symbol} (${mode.count}×)`;
}

const dateWithWeekdayFormat = new Intl.DateTimeFormat("hu-HU", {
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  weekday: "short",
  timeZone: "UTC",
});

const dateFormat = new Intl.DateTimeFormat("hu-HU", {
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  timeZone: "UTC",
});

/** ISO dátum magyarul, a hét napjával, pl. „2023. 07. 01., Szo”. */
export function formatDate(isoDate: string): string {
  return dateWithWeekdayFormat.format(parseIsoDate(isoDate));
}

/** Időszak magyarul, pl. „2023. 07. 01. – 2023. 07. 31.”. */
export function formatPeriod(startDate: string, endDate: string): string {
  return `${dateFormat.format(parseIsoDate(startDate))} – ${dateFormat.format(parseIsoDate(endDate))}`;
}

function parseIsoDate(isoDate: string): Date {
  return new Date(`${isoDate}T00:00:00Z`);
}
