import { describe, expect, it } from "vitest";
import { MAX_DAYS, validateDateRange } from "./dateRange.ts";

describe("validateDateRange", () => {
  it("érvényes időszakra nincs hiba", () => {
    expect(validateDateRange("2023-07-01", "2023-07-31")).toBeNull();
  });

  it("egyetlen nap is érvényes", () => {
    expect(validateDateRange("2023-07-01", "2023-07-01")).toBeNull();
  });

  it(`egy teljes szökőév (${MAX_DAYS} nap) még érvényes`, () => {
    expect(validateDateRange("2020-01-01", "2020-12-31")).toBeNull();
  });

  it("az adatok két határnapja érvényes", () => {
    expect(validateDateRange("1901-01-01", "1901-01-31")).toBeNull();
    expect(validateDateRange("2023-12-01", "2023-12-31")).toBeNull();
  });

  it("hiányzó dátum", () => {
    expect(validateDateRange("", "2023-07-31")).toBe(
      "Add meg a kezdő és a záró dátumot.",
    );
  });

  it("nem létező nap", () => {
    expect(validateDateRange("2023-02-30", "2023-03-31")).toBe(
      "Érvénytelen kezdő dátum.",
    );
    expect(validateDateRange("2023-02-01", "2023-02-29")).toBe(
      "Érvénytelen záró dátum.",
    );
  });

  it("a kezdő dátum nem lehet a záró után", () => {
    expect(validateDateRange("2023-08-01", "2023-07-31")).toBe(
      "A kezdő dátum nem lehet későbbi a záró dátumnál.",
    );
  });

  it("az adatok időszakán kívül", () => {
    const message = "Adatok csak 1901-01-01 és 2023-12-31 között érhetők el.";
    expect(validateDateRange("1900-12-31", "1901-01-05")).toBe(message);
    expect(validateDateRange("2023-12-30", "2024-01-01")).toBe(message);
  });

  it(`${MAX_DAYS} napnál hosszabb időszak`, () => {
    expect(validateDateRange("2022-01-01", "2023-01-02")).toBe(
      "Legfeljebb 366 napos időszak kérhető le.",
    );
  });

  it("az óraátállítás nem zavarja a napok számolását", () => {
    // Az időszak 2023-03-26-ot (óraátállítás) is tartalmazza.
    // 2022-10-01 – 2023-10-01 pontosan 366 nap, egy nappal több már nem fér bele.
    expect(validateDateRange("2022-10-01", "2023-10-01")).toBeNull();
    expect(validateDateRange("2022-10-01", "2023-10-02")).toBe(
      "Legfeljebb 366 napos időszak kérhető le.",
    );
  });
});
