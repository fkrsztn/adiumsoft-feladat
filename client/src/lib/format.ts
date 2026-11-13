import type { Mode } from "../api/types.ts";

/** Hiányzó mérés szövege a táblázatban. */
export const MISSING_VALUE_TEXT = "nincs adat";

/** Ennyi holtversenyes módusz értéket írunk ki, a többit „+N” jelzi. */
const MAX_LISTED_MODES = 3;

// signDisplay "negative": a -0 ne „-0,0”-ként jelenjen meg.
const measurementFormat = new Intl.NumberFormat("hu-HU", {
  minimumFractionDigits: 1,
  maximumFractionDigits: 1,
  signDisplay: "negative",
});

// A medián páros elemszámnál két tizedes is lehet (pl. 2,15).
const medianFormat = new Intl.NumberFormat("hu-HU", {
  minimumFractionDigits: 1,
  maximumFractionDigits: 2,
  signDisplay: "negative",
});

const dateOnlyFormat = new Intl.DateTimeFormat("hu-HU", {
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  timeZone: "UTC",
});

const dateFormat = new Intl.DateTimeFormat("hu-HU", {
  year: "numeric",
  month: "2-digit",
  day: "2-digit",
  weekday: "short",
  timeZone: "UTC",
});

/** Mért érték mértékegységgel, pl. „12,3 °C”; hiányzó értékre „nincs adat”. */
export function formatMeasurement(value: number | null, unit: string): string {
  return value === null
    ? MISSING_VALUE_TEXT
    : `${measurementFormat.format(value)} ${unit}`;
}

/** Medián mértékegységgel, pl. „2,15 °C”. */
export function formatMedian(value: number | null, unit: string): string {
  return value === null
    ? MISSING_VALUE_TEXT
    : `${medianFormat.format(value)} ${unit}`;
}

/**
 * Módusz, pl. „12,3 °C (3×)”, holtversenyben „12,3 / 14,1 °C (3×)”; sok
 * értéknél az első néhány után „+N”. Ha nincs módusz: „nincs”.
 */
export function formatMode(mode: Mode, unit: string): string {
  if (mode.values.length === 0) {
    return "nincs";
  }
  const listed = mode.values
    .slice(0, MAX_LISTED_MODES)
    .map((value) => measurementFormat.format(value))
    .join(" / ");
  const hidden = mode.values.length - MAX_LISTED_MODES;
  const more = hidden > 0 ? ` +${hidden}` : "";
  return `${listed}${more} ${unit} (${mode.count}×)`;
}

/** ISO dátum magyarul, pl. „2023. 07. 01., Szo”. */
export function formatDate(isoDate: string): string {
  return formatIsoDate(dateFormat, isoDate);
}

/** Időszak magyarul, pl. „2023. 07. 01. – 2023. 07. 31.”. */
export function formatPeriod(startDate: string, endDate: string): string {
  return `${formatIsoDate(dateOnlyFormat, startDate)} – ${formatIsoDate(dateOnlyFormat, endDate)}`;
}

function formatIsoDate(format: Intl.DateTimeFormat, isoDate: string): string {
  return format.format(new Date(`${isoDate}T00:00:00Z`));
}
