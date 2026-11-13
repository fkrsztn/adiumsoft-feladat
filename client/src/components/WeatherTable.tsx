import type {
  ColumnStatistics,
  Units,
  WeatherDay,
  WeatherSummary,
} from "../api/types.ts";
import {
  formatDate,
  formatMeasurement,
  formatMedian,
  formatMode,
  formatPeriod,
} from "../lib/format.ts";

type Column = {
  key: keyof WeatherSummary;
  label: string;
  unit: (units: Units) => string;
};

const COLUMNS: Column[] = [
  { key: "minTemp", label: "Minimum", unit: (units) => units.temperature },
  { key: "maxTemp", label: "Maximum", unit: (units) => units.temperature },
  { key: "avgTemp", label: "Átlag", unit: (units) => units.temperature },
  {
    key: "precipitation",
    label: "Csapadék",
    unit: (units) => units.precipitation,
  },
];

type WeatherTableProps = {
  /** A táblázat címe, pl. a város neve. */
  title: string;
  /** Kiegészítő sor a cím alatt, pl. az átlagolt városok listája. */
  description?: string;
  startDate: string;
  endDate: string;
  days: WeatherDay[];
  summary: WeatherSummary;
  units: Units;
};

/**
 * Napi időjárás táblázat, a láblécben oszloponként mediánnal és módusszal.
 * A város- és a régióoldal is ezt használja.
 *
 * A fejléc és a lábléc görgetéskor is látható marad.
 */
export function WeatherTable({
  title,
  description,
  startDate,
  endDate,
  days,
  summary,
  units,
}: WeatherTableProps) {
  const period = formatPeriod(startDate, endDate);

  return (
    <section aria-label={`${title}, ${period}`} className="space-y-3">
      <header>
        <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
        <p className="text-sm text-slate-600">
          {period} · {days.length} nap
          {description !== undefined && ` · ${description}`}
        </p>
      </header>
      <div className="max-h-[70vh] overflow-auto rounded-lg border border-slate-200 shadow-sm">
        <table className="w-full border-separate border-spacing-0 text-sm">
          <caption className="sr-only">
            {title}, {period}
          </caption>
          <thead className="sticky top-0 bg-slate-100 text-left text-slate-700 shadow-[0_1px_0_var(--color-slate-200)]">
            <tr>
              <th scope="col" className="px-4 py-2 font-semibold">
                Dátum
              </th>
              {COLUMNS.map((column) => (
                <th
                  key={column.key}
                  scope="col"
                  className="px-4 py-2 text-right font-semibold"
                >
                  {column.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-white">
            {days.map((day) => (
              <tr key={day.date} className="hover:bg-sky-50/60">
                <th
                  scope="row"
                  className="whitespace-nowrap border-b border-slate-100 px-4 py-1.5 text-left font-normal text-slate-600"
                >
                  {formatDate(day.date)}
                </th>
                {COLUMNS.map((column) => (
                  <MeasurementCell
                    key={column.key}
                    value={day[column.key]}
                    unit={column.unit(units)}
                  />
                ))}
              </tr>
            ))}
          </tbody>
          <tfoot className="sticky bottom-0 bg-slate-100 text-slate-800 shadow-[0_-1px_0_var(--color-slate-300)]">
            <SummaryRow
              label="Medián"
              render={(statistics, unit) =>
                formatMedian(statistics.median, unit)
              }
              summary={summary}
              units={units}
            />
            <SummaryRow
              label="Módusz"
              render={(statistics, unit) => formatMode(statistics.mode, unit)}
              summary={summary}
              units={units}
            />
          </tfoot>
        </table>
      </div>
    </section>
  );
}

function MeasurementCell({
  value,
  unit,
}: {
  value: number | null;
  unit: string;
}) {
  return (
    <td
      className={`whitespace-nowrap border-b border-slate-100 px-4 py-1.5 text-right tabular-nums ${
        value === null ? "italic text-slate-400" : "text-slate-900"
      }`}
    >
      {formatMeasurement(value, unit)}
    </td>
  );
}

type SummaryRowProps = {
  label: string;
  render: (statistics: ColumnStatistics, unit: string) => string;
  summary: WeatherSummary;
  units: Units;
};

function SummaryRow({ label, render, summary, units }: SummaryRowProps) {
  return (
    <tr>
      <th
        scope="row"
        className="border-t border-slate-200 px-4 py-2 text-left font-semibold"
      >
        {label}
      </th>
      {COLUMNS.map((column) => (
        <td
          key={column.key}
          className="border-t border-slate-200 px-4 py-2 text-right tabular-nums"
        >
          {render(summary[column.key], column.unit(units))}
        </td>
      ))}
    </tr>
  );
}
