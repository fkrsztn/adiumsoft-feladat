/**
 * Hőtérkép színek a hőmérséklet cellákhoz: hideg kék → semleges → meleg
 * narancsvörös. Mindig a °C értékből számol, így a szín a mértékegység
 * váltásakor nem változik.
 *
 * Minden szín elég világos ahhoz, hogy a sötét szöveg olvasható maradjon
 * rajta (a legsötétebb színen is legalább 4,5:1 kontraszt).
 */

type Rgb = readonly [number, number, number];

/** Színpontok (°C → szín); köztük lineárisan átmenetet számolunk. */
const STOPS: readonly (readonly [number, Rgb])[] = [
  [-20, [143, 176, 218]],
  [-5, [201, 218, 236]],
  [10, [238, 242, 241]],
  [22, [246, 210, 184]],
  [35, [229, 154, 115]],
];

/** A hőmérséklethez tartozó háttérszín CSS formában, pl. `rgb(246 210 184)`. */
export function temperatureColor(celsius: number): string {
  const [r, g, b] = interpolate(celsius);
  return `rgb(${r} ${g} ${b})`;
}

function interpolate(celsius: number): Rgb {
  const first = STOPS[0];
  const last = STOPS[STOPS.length - 1];
  if (celsius <= first[0]) {
    return first[1];
  }
  if (celsius >= last[0]) {
    return last[1];
  }
  const upperIndex = STOPS.findIndex(([limit]) => celsius < limit);
  const [lowerLimit, lowerColor] = STOPS[upperIndex - 1];
  const [upperLimit, upperColor] = STOPS[upperIndex];
  const ratio = (celsius - lowerLimit) / (upperLimit - lowerLimit);
  const mix = (channel: 0 | 1 | 2) =>
    Math.round(
      lowerColor[channel] + (upperColor[channel] - lowerColor[channel]) * ratio,
    );
  return [mix(0), mix(1), mix(2)];
}
