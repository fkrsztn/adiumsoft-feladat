import { fetchRegions, fetchRegionWeather } from "../api/client.ts";
import type { RegionWeatherReport } from "../api/types.ts";
import {
  WeatherQueryForm,
  type SelectOption,
} from "../components/WeatherQueryForm.tsx";
import { WeatherResult } from "../components/WeatherResult.tsx";
import { mapRequestState, useLoad, useRequest } from "../hooks/useRequest.ts";
import type { UnitSystem } from "../lib/units.ts";

function toOptions(regions: string[]): SelectOption[] {
  return regions.map((region) => ({ value: region, label: region }));
}

/**
 * Régióoldal: a régió városainak napi átlaga („virtuális állomás”). Az
 * átlagot a szerver számolja; a táblázat felett látszik, mely városoké.
 */
export function RegionPage({ unitSystem }: { unitSystem: UnitSystem }) {
  const regions = useLoad(fetchRegions);
  const weather = useRequest<RegionWeatherReport>();

  return (
    <section aria-label="Régió időjárása" className="space-y-6">
      <WeatherQueryForm
        selectLabel="Régió"
        placeholder="Válassz régiót…"
        missingSelectionMessage="Válassz régiót."
        options={mapRequestState(regions, toOptions)}
        loading={weather.state.status === "loading"}
        onSubmit={({ selection, startDate, endDate }) =>
          weather.run((signal) =>
            fetchRegionWeather(
              { region: selection, startDate, endDate },
              signal,
            ),
          )
        }
      />
      <WeatherResult
        state={weather.state}
        unitSystem={unitSystem}
        idleMessage="Válassz régiót és időszakot. A táblázat a régió városainak napi átlagát mutatja."
        describe={(report) => ({
          title: report.region.name,
          description: `${report.region.cities.length} város átlaga: ${report.region.cities
            .map((city) => city.name)
            .join(", ")}`,
        })}
      />
    </section>
  );
}
