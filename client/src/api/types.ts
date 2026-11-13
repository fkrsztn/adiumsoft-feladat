/**
 * Az API válaszainak típusai. A mezőnevek és a szerkezet az
 * `api/src/Controllers` válaszaival egyezik.
 */

export type City = {
  id: number;
  name: string;
  region: string;
};

/** Egy nap adatai. A hiányzó mérés `null` (a forrásban -999). */
export type WeatherDay = {
  /** ISO dátum, pl. `2023-07-01`. */
  date: string;
  minTemp: number | null;
  maxTemp: number | null;
  /** (min + max) / 2, a szerver számolja. */
  avgTemp: number | null;
  precipitation: number | null;
};

/** Módusz: üres `values` esetén nincs módusz. */
export type Mode = {
  values: number[];
  count: number;
};

export type ColumnStatistics = {
  median: number | null;
  mode: Mode;
};

export type WeatherSummary = {
  minTemp: ColumnStatistics;
  maxTemp: ColumnStatistics;
  avgTemp: ColumnStatistics;
  precipitation: ColumnStatistics;
};

export type Units = {
  temperature: string;
  precipitation: string;
};

/** A városi és a régiós válasz közös része. */
export type WeatherReport = {
  startDate: string;
  endDate: string;
  units: Units;
  days: WeatherDay[];
  summary: WeatherSummary;
};

export type CityWeatherReport = WeatherReport & {
  city: City;
};

export type RegionWeatherReport = WeatherReport & {
  region: {
    name: string;
    cities: Pick<City, "id" | "name">[];
  };
};

export type DateRangeQuery = {
  startDate: string;
  endDate: string;
};
