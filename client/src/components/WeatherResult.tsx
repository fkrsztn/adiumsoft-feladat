import type { WeatherReport } from "../api/types.ts";
import type { RequestState } from "../hooks/useRequest.ts";
import type { UnitSystem } from "../lib/units.ts";
import { StatusMessage } from "./StatusMessage.tsx";
import { WeatherTable } from "./WeatherTable.tsx";

type WeatherResultProps<T extends WeatherReport> = {
  state: RequestState<T>;
  unitSystem: UnitSystem;
  /** Útmutató, amíg még nem volt lekérdezés. */
  idleMessage: string;
  /** A táblázat címe és alcíme a válaszból (pl. a város neve és régiója). */
  describe: (report: T) => { title: string; description: string };
};

/**
 * A lekérdezés eredménye az állapotnak megfelelően: útmutató, betöltés,
 * hiba, üres eredmény vagy a táblázat.
 */
export function WeatherResult<T extends WeatherReport>({
  state,
  unitSystem,
  idleMessage,
  describe,
}: WeatherResultProps<T>) {
  switch (state.status) {
    case "idle":
      return <StatusMessage kind="info">{idleMessage}</StatusMessage>;
    case "loading":
      return <StatusMessage kind="info">Adatok betöltése…</StatusMessage>;
    case "error":
      return <StatusMessage kind="error">{state.message}</StatusMessage>;
    case "success": {
      const report = state.data;
      if (report.days.length === 0) {
        return (
          <StatusMessage kind="info">
            A kiválasztott időszakra nincs adat.
          </StatusMessage>
        );
      }
      const { title, description } = describe(report);
      return (
        <WeatherTable
          title={title}
          description={description}
          startDate={report.startDate}
          endDate={report.endDate}
          days={report.days}
          summary={report.summary}
          unitSystem={unitSystem}
        />
      );
    }
  }
}
