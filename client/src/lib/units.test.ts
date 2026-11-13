import { describe, expect, it } from "vitest";
import { celsiusToFahrenheit, millimetersToInches, unitFor } from "./units.ts";

describe("celsiusToFahrenheit", () => {
  it.each([
    [0, 32],
    [100, 212],
    [-40, -40],
    [37, 98.6],
  ])("%d °C = %d °F", (celsius, fahrenheit) => {
    expect(celsiusToFahrenheit(celsius)).toBeCloseTo(fahrenheit, 10);
  });
});

describe("millimetersToInches", () => {
  it("25,4 mm = 1 inch", () => {
    expect(millimetersToInches(25.4)).toBe(1);
  });

  it("0 mm = 0 inch", () => {
    expect(millimetersToInches(0)).toBe(0);
  });
});

describe("unitFor", () => {
  it("SI egységben nem vált át", () => {
    expect(unitFor("metric", "temperature").fromSi(12.3)).toBe(12.3);
    expect(unitFor("metric", "precipitation").symbol).toBe("mm");
  });

  it("angolszász egységben °F-re és inch-re vált", () => {
    expect(unitFor("imperial", "temperature").fromSi(10)).toBe(50);
    expect(unitFor("imperial", "precipitation").fromSi(50.8)).toBe(2);
  });
});
