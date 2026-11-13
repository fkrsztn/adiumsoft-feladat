import { useId, useState, type FormEvent } from "react";
import { fetchCities, fetchCityWeather } from "../api/client.ts";
import type { CityWeatherReport } from "../api/types.ts";
import { DateRangeFields } from "../components/DateRangeFields.tsx";
import { inputClass, labelClass } from "../components/fieldStyles.ts";
import { StatusMessage } from "../components/StatusMessage.tsx";
import { WeatherTable } from "../components/WeatherTable.tsx";
import { useLoad, useRequest } from "../hooks/useRequest.ts";
import { validateDateRange } from "../lib/dateRange.ts";

const DEFAULT_START_DATE = "2023-07-01";
const DEFAULT_END_DATE = "2023-07-31";

/**
 * Városoldal: város és időszak kiválasztása, majd a napi adatok táblázata.
 */
export function CityPage() {
  const cityFieldId = useId();
  const cities = useLoad(fetchCities);
  const weather = useRequest<CityWeatherReport>();

  const [cityId, setCityId] = useState("");
  const [startDate, setStartDate] = useState(DEFAULT_START_DATE);
  const [endDate, setEndDate] = useState(DEFAULT_END_DATE);
  const [validationError, setValidationError] = useState<string | null>(null);

  const loading = weather.state.status === "loading";

  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const error =
      cityId === "" ? "Válassz várost." : validateDateRange(startDate, endDate);
    setValidationError(error);
    if (error !== null) {
      return;
    }
    weather.run((signal) =>
      fetchCityWeather({ cityId: Number(cityId), startDate, endDate }, signal),
    );
  };

  return (
    <section aria-labelledby="city-page-title" className="space-y-6">
      <h2 id="city-page-title" className="sr-only">
        Város időjárása
      </h2>

      <form
        onSubmit={onSubmit}
        noValidate
        className="grid gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[2fr_1fr_1fr_auto] sm:items-end"
      >
        <div>
          <label htmlFor={cityFieldId} className={labelClass}>
            Város
          </label>
          <select
            id={cityFieldId}
            required
            value={cityId}
            onChange={(event) => setCityId(event.target.value)}
            disabled={cities.status !== "success"}
            className={inputClass}
          >
            <option value="">
              {cities.status === "loading"
                ? "Városok betöltése…"
                : "Válassz várost…"}
            </option>
            {cities.status === "success" &&
              cities.data.map((city) => (
                <option key={city.id} value={city.id}>
                  {city.name} ({city.region})
                </option>
              ))}
          </select>
        </div>

        <DateRangeFields
          startDate={startDate}
          endDate={endDate}
          onStartDateChange={setStartDate}
          onEndDateChange={setEndDate}
        />

        <button
          type="submit"
          disabled={loading || cities.status !== "success"}
          className="rounded-md bg-sky-600 px-5 py-2 font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-400"
        >
          {loading ? "Betöltés…" : "Lekérdezés"}
        </button>
      </form>

      {cities.status === "error" && (
        <StatusMessage kind="error">
          A városlista nem tölthető be: {cities.message}
        </StatusMessage>
      )}
      {validationError !== null && (
        <StatusMessage kind="error">{validationError}</StatusMessage>
      )}

      <CityWeatherResult state={weather.state} />
    </section>
  );
}

function CityWeatherResult({
  state,
}: {
  state: ReturnType<typeof useRequest<CityWeatherReport>>["state"];
}) {
  switch (state.status) {
    case "idle":
      return (
        <StatusMessage kind="info">
          Válassz várost és időszakot, majd kattints a Lekérdezés gombra.
        </StatusMessage>
      );
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
      return (
        <WeatherTable
          title={report.city.name}
          description={report.city.region}
          startDate={report.startDate}
          endDate={report.endDate}
          days={report.days}
          summary={report.summary}
          units={report.units}
        />
      );
    }
  }
}
