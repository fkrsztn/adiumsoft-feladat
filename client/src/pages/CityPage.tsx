import { fetchCities, fetchCityWeather } from "../api/client.ts";
import type { City, CityWeatherReport } from "../api/types.ts";
import {
  WeatherQueryForm,
  type SelectOption,
} from "../components/WeatherQueryForm.tsx";
import { WeatherResult } from "../components/WeatherResult.tsx";
import { mapRequestState, useLoad, useRequest } from "../hooks/useRequest.ts";
import type { UnitSystem } from "../lib/units.ts";

function toOptions(cities: City[]): SelectOption[] {
  return cities.map((city) => ({
    value: String(city.id),
    label: `${city.name} (${city.region})`,
  }));
}

/**
 * Városoldal: egy mérőállomás napi adatai a választott időszakban.
 */
export function CityPage({ unitSystem }: { unitSystem: UnitSystem }) {
  const cities = useLoad(fetchCities);
  const weather = useRequest<CityWeatherReport>();

  return (
    <section aria-label="Város időjárása" className="space-y-6">
      <WeatherQueryForm
        selectLabel="Város"
        placeholder="Válassz várost…"
        missingSelectionMessage="Válassz várost."
        options={mapRequestState(cities, toOptions)}
        loading={weather.state.status === "loading"}
        onSubmit={({ selection, startDate, endDate }) =>
          weather.run((signal) =>
            fetchCityWeather(
              { cityId: Number(selection), startDate, endDate },
              signal,
            ),
          )
        }
      />
      <WeatherResult
        state={weather.state}
        unitSystem={unitSystem}
        idleMessage="Válassz várost és időszakot, majd kattints a Lekérdezés gombra."
        describe={(report) => ({
          title: report.city.name,
          description: report.city.region,
        })}
      />
    </section>
  );
}
