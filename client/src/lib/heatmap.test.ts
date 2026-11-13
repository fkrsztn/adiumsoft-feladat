import { describe, expect, it } from "vitest";
import { temperatureColor } from "./heatmap.ts";

describe("temperatureColor", () => {
  it("a színpontokon pontosan a megadott színt adja", () => {
    expect(temperatureColor(-20)).toBe("rgb(143 176 218)");
    expect(temperatureColor(10)).toBe("rgb(238 242 241)");
    expect(temperatureColor(35)).toBe("rgb(229 154 115)");
  });

  it("két színpont között átmenetet számol", () => {
    // -5 és 10 °C között félúton.
    expect(temperatureColor(2.5)).toBe("rgb(220 230 239)");
  });

  it("a skálán kívül a szélső színt adja", () => {
    expect(temperatureColor(-35)).toBe(temperatureColor(-20));
    expect(temperatureColor(42)).toBe(temperatureColor(35));
  });
});
