import type {
  ColumnStatistics,
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
import { temperatureColor } from "../lib/heatmap.ts";
import {
  unitFor,
  type MeasurementKind,
  type UnitSystem,
} from "../lib/units.ts";

type Column = {
  key: keyof WeatherSummary;
  label: string;
  kind: MeasurementKind;
};

const COLUMNS: Column[] = [
  { key: "minTemp", label: "Minimum", kind: "temperature" },
  { key: "maxTemp", label: "Maximum", kind: "temperature" },
  { key: "avgTemp", label: "Átlag", kind: "temperature" },
  { key: "precipitation", label: "Csapadék", kind: "precipitation" },
];

/** A csapadék sáv ennyi mm-nél éri el a teljes szélességet. */
const PRECIPITATION_BAR_FULL_MM = 20;

type WeatherTableProps = {
  /** A táblázat címe, pl. a város neve. */
  title: string;
  /** Kiegészítő sor a cím alatt, pl. az átlagolt városok listája. */
  description: string;
  startDate: string;
  endDate: string;
  days: WeatherDay[];
  summary: WeatherSummary;
  unitSystem: UnitSystem;
};

/**
 * Napi időjárás táblázat, a láblécben oszloponként mediánnal és módusszal.
 * A város- és a régióoldal is ezt használja.
 *
 * A hőmérséklet cellák háttere hőtérkép (hideg kék → meleg narancs), a
 * csapadék mellett sáv mutatja a mennyiséget. A fejléc és a lábléc
 * görgetéskor is látható marad.
 */
export function WeatherTable({
  title,
  description,
  startDate,
  endDate,
  days,
  summary,
  unitSystem,
}: WeatherTableProps) {
  const period = formatPeriod(startDate, endDate);

  return (
    <section aria-label={`${title}, ${period}`} className="space-y-3">
      <header>
        <h2 className="font-display text-3xl leading-tight font-bold">
          {title}
        </h2>
        <p className="text-sm text-ink-muted">
          {period} · {days.length} nap · {description}
        </p>
      </header>

      <div className="max-h-[70vh] overflow-auto border-y-2 border-ink">
        <table className="w-full min-w-[40rem] table-fixed border-separate border-spacing-0 tabular-nums">
          <caption className="sr-only">
            {title}, {period}
          </caption>
          {/* Egyforma szélességű értékoszlopok, hogy a számok egy vonalban legyenek. */}
          <colgroup>
            <col className="w-[24%]" />
            {COLUMNS.map((column) => (
              <col key={column.key} />
            ))}
          </colgroup>
          <thead className="sticky top-0 z-10 bg-paper">
            <tr>
              <HeaderCell align="left">Dátum</HeaderCell>
              {COLUMNS.map((column) => (
                <HeaderCell key={column.key} align="right">
                  {column.label}
                </HeaderCell>
              ))}
            </tr>
          </thead>
          <tbody>
            {days.map((day) => (
              <tr key={day.date} className="hover:bg-paper-deep">
                <th
                  scope="row"
                  className="border-b border-rule px-3 py-1.5 text-left font-normal whitespace-nowrap text-ink-muted"
                >
                  {formatDate(day.date)}
                </th>
                {COLUMNS.map((column) =>
                  column.kind === "temperature" ? (
                    <TemperatureCell
                      key={column.key}
                      celsius={day[column.key]}
                      unitSystem={unitSystem}
                    />
                  ) : (
                    <PrecipitationCell
                      key={column.key}
                      millimeters={day[column.key]}
                      unitSystem={unitSystem}
                    />
                  ),
                )}
              </tr>
            ))}
          </tbody>
          <tfoot className="sticky bottom-0 bg-paper">
            <SummaryRow
              label="Medián"
              strong
              render={(statistics, kind) =>
                formatMedian(statistics.median, unitFor(unitSystem, kind))
              }
              summary={summary}
            />
            <SummaryRow
              label="Módusz"
              render={(statistics, kind) =>
                formatMode(statistics.mode, unitFor(unitSystem, kind))
              }
              summary={summary}
            />
          </tfoot>
        </table>
      </div>
    </section>
  );
}

function HeaderCell({
  align,
  children,
}: {
  align: "left" | "right";
  children: string;
}) {
  return (
    <th
      scope="col"
      className={`font-display border-b border-ink px-3 py-2 text-sm font-medium tracking-[0.12em] text-ink-muted uppercase ${
        align === "left" ? "text-left" : "text-right"
      }`}
    >
      {children}
    </th>
  );
}

function MissingValue() {
  return <span className="text-ink-faint italic">nincs adat</span>;
}

function TemperatureCell({
  celsius,
  unitSystem,
}: {
  celsius: number | null;
  unitSystem: UnitSystem;
}) {
  return (
    <td
      className="border-b border-rule px-3 py-1.5 text-right whitespace-nowrap"
      style={
        celsius === null
          ? undefined
          : { backgroundColor: temperatureColor(celsius) }
      }
    >
      {celsius === null ? (
        <MissingValue />
      ) : (
        formatMeasurement(celsius, unitFor(unitSystem, "temperature"))
      )}
    </td>
  );
}

function PrecipitationCell({
  millimeters,
  unitSystem,
}: {
  millimeters: number | null;
  unitSystem: UnitSystem;
}) {
  if (millimeters === null) {
    return (
      <td className="border-b border-rule px-3 py-1.5 text-right">
        <MissingValue />
      </td>
    );
  }
  const barPercent = Math.min(millimeters / PRECIPITATION_BAR_FULL_MM, 1) * 100;
  return (
    <td className="border-b border-rule px-3 py-1.5 text-right whitespace-nowrap">
      <span className="inline-flex items-center justify-end gap-2">
        <span aria-hidden="true" className="inline-block h-1.5 w-12">
          <span
            className="ml-auto block h-full bg-accent"
            style={{ width: `${barPercent}%` }}
          />
        </span>
        {formatMeasurement(millimeters, unitFor(unitSystem, "precipitation"))}
      </span>
    </td>
  );
}

type SummaryRowProps = {
  label: string;
  strong?: boolean;
  render: (statistics: ColumnStatistics, kind: MeasurementKind) => string;
  summary: WeatherSummary;
};

function SummaryRow({
  label,
  strong = false,
  render,
  summary,
}: SummaryRowProps) {
  const border = strong ? "border-t-2 border-ink" : "border-t border-rule";
  return (
    <tr>
      <th
        scope="row"
        className={`font-display ${border} px-3 py-2 text-left font-medium tracking-[0.12em] uppercase`}
      >
        {label}
      </th>
      {COLUMNS.map((column) => (
        <td
          key={column.key}
          className={`${border} px-3 py-2 text-right ${strong ? "font-semibold" : "text-sm text-ink-muted"}`}
        >
          {render(summary[column.key], column.kind)}
        </td>
      ))}
    </tr>
  );
}
