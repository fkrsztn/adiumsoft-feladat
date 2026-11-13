import type {
  City,
  CityWeatherReport,
  DateRangeQuery,
  RegionWeatherReport,
} from "./types.ts";

/**
 * Az API címe. Fejlesztéskor `http://localhost:8080`, a prod buildben
 * `http://localhost:8300/api/v1` (lásd `docker/prod.Dockerfile`).
 */
const API_URL: string = import.meta.env.VITE_API_URL;

/**
 * Az API hibája, a felhasználónak megjeleníthető (magyar) üzenettel.
 */
export class ApiError extends Error {
  readonly status: number | null;

  constructor(message: string, status: number | null) {
    super(message);
    this.name = "ApiError";
    this.status = status;
  }
}

export function fetchCities(signal?: AbortSignal): Promise<City[]> {
  return getJson<{ cities: City[] }>("/cities", {}, signal).then(
    (body) => body.cities,
  );
}

export function fetchRegions(signal?: AbortSignal): Promise<string[]> {
  return getJson<{ regions: string[] }>("/regions", {}, signal).then(
    (body) => body.regions,
  );
}

export function fetchCityWeather(
  query: DateRangeQuery & { cityId: number },
  signal?: AbortSignal,
): Promise<CityWeatherReport> {
  return getJson<CityWeatherReport>(
    "/weather/city",
    {
      cityId: String(query.cityId),
      startDate: query.startDate,
      endDate: query.endDate,
    },
    signal,
  );
}

export function fetchRegionWeather(
  query: DateRangeQuery & { region: string },
  signal?: AbortSignal,
): Promise<RegionWeatherReport> {
  return getJson<RegionWeatherReport>("/weather/region", query, signal);
}

/**
 * GET kérés, JSON válasszal. Hibánál `ApiError`-t dob, a szerver
 * hibaüzenetével, ha van.
 *
 * A válasz szerkezetét nem ellenőrzi futásidőben: a saját API-nkról van szó,
 * a típusok a szerver válaszaival egyeznek.
 */
async function getJson<T>(
  path: string,
  params: Record<string, string>,
  signal?: AbortSignal,
): Promise<T> {
  // URLSearchParams: az ékezetes és szóközt tartalmazó értékeket
  // (pl. „Észak és Alföld”) helyesen kódolja.
  const query = new URLSearchParams(params).toString();
  const url = `${API_URL}${path}${query === "" ? "" : `?${query}`}`;

  let response: Response;
  try {
    response = await fetch(url, { signal });
  } catch (error) {
    if (signal?.aborted) {
      throw error;
    }
    throw new ApiError(
      "Nem sikerült elérni a szervert. Ellenőrizd, hogy fut-e az API.",
      null,
    );
  }

  const body: unknown = await response.json().catch(() => null);
  if (!response.ok) {
    throw new ApiError(
      errorMessageFrom(body, response.status),
      response.status,
    );
  }
  return body as T;
}

function errorMessageFrom(body: unknown, status: number): string {
  if (
    typeof body === "object" &&
    body !== null &&
    "error" in body &&
    typeof body.error === "string"
  ) {
    return body.error;
  }
  return `Váratlan hiba történt (HTTP ${status}).`;
}
