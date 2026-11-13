import { describe, expect, it } from "vitest";
import {
  formatDate,
  formatMeasurement,
  formatMedian,
  formatMode,
  formatPeriod,
} from "./format.ts";
import { unitFor } from "./units.ts";

const celsius = unitFor("metric", "temperature");
const fahrenheit = unitFor("imperial", "temperature");
const millimeters = unitFor("metric", "precipitation");
const inches = unitFor("imperial", "precipitation");

describe("formatMeasurement", () => {
  it("magyar tizedesvesszővel és mértékegységgel ír", () => {
    expect(formatMeasurement(12.3, celsius)).toBe("12,3 °C");
    expect(formatMeasurement(-3.4, celsius)).toBe("-3,4 °C");
    expect(formatMeasurement(0, millimeters)).toBe("0,0 mm");
  });

  it("átvált a választott mértékegységre", () => {
    expect(formatMeasurement(20, fahrenheit)).toBe("68,0 °F");
    expect(formatMeasurement(2.9, inches)).toBe("0,11 in");
  });

  it("hiányzó értékre „nincs adat”", () => {
    expect(formatMeasurement(null, celsius)).toBe("nincs adat");
  });

  it("nem ír negatív nullát", () => {
    // -17,8 °C = -0,04 °F, egy tizedesre kerekítve -0,0 lenne.
    expect(formatMeasurement(-17.8, fahrenheit)).toBe("0,0 °F");
  });
});

describe("formatMedian", () => {
  it("SI egységben két tizedest is mutat (páros elemszám)", () => {
    expect(formatMedian(2.15, celsius)).toBe("2,15 °C");
    expect(formatMedian(19.2, celsius)).toBe("19,2 °C");
  });

  it("°F-ben egy tizedesre kerekít", () => {
    expect(formatMedian(17.1, fahrenheit)).toBe("62,8 °F");
  });

  it("hiányzó mediánra „nincs adat”", () => {
    expect(formatMedian(null, millimeters)).toBe("nincs adat");
  });
});

describe("formatMode", () => {
  it("egy módusz a darabszámmal", () => {
    expect(formatMode({ values: [13], count: 11 }, celsius)).toBe(
      "13,0 °C (11×)",
    );
  });

  it("holtverseny: az értékek perjellel elválasztva", () => {
    expect(formatMode({ values: [23.9, 27.4], count: 3 }, celsius)).toBe(
      "23,9 / 27,4 °C (3×)",
    );
  });

  it("háromnál több értéknél „+N”", () => {
    expect(
      formatMode({ values: [2.3, 4.9, 6.6, 17.5, 23.4], count: 4 }, celsius),
    ).toBe("2,3 / 4,9 / 6,6 +2 °C (4×)");
  });

  it("nincs módusz: „nincs”", () => {
    expect(formatMode({ values: [], count: 0 }, celsius)).toBe("nincs");
  });

  it("átváltja az értékeket", () => {
    expect(formatMode({ values: [0], count: 20 }, fahrenheit)).toBe(
      "32,0 °F (20×)",
    );
  });
});

describe("dátumok", () => {
  it("napot a hét napjával írja ki", () => {
    expect(formatDate("2023-07-01")).toBe("2023. 07. 01., Szo");
  });

  it("időszakot a hét napja nélkül írja ki", () => {
    expect(formatPeriod("2023-07-01", "2023-07-31")).toBe(
      "2023. 07. 01. – 2023. 07. 31.",
    );
  });
});
