import { useSyncExternalStore } from "react";

/**
 * Az oldalak címei a hash-ben (`#/varos`, `#/regio`).
 *
 * Hash alapú routing, mert a prod nginx nem irányít vissza minden útvonalat
 * az `index.html`-re: egy `/regio` címet frissítéskor 404-gyel szolgálna ki.
 * A hash részt a böngésző nem küldi el a szervernek, így ez mindig működik.
 */
export const ROUTES = ["varos", "regio"] as const;

export type Route = (typeof ROUTES)[number];

const DEFAULT_ROUTE: Route = "varos";

export function routeHref(route: Route): string {
  return `#/${route}`;
}

/**
 * Az aktuális oldal a címsor hash részéből; ismeretlen vagy üres hash esetén
 * az alapértelmezett oldal.
 */
export function useHashRoute(): Route {
  return useSyncExternalStore(subscribe, () => parseHash(window.location.hash));
}

function parseHash(hash: string): Route {
  const name = hash.replace(/^#\/?/, "");
  return ROUTES.find((route) => route === name) ?? DEFAULT_ROUTE;
}

function subscribe(onChange: () => void): () => void {
  window.addEventListener("hashchange", onChange);
  return () => window.removeEventListener("hashchange", onChange);
}
