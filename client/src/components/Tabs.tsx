import { routeHref, type Route } from "../hooks/useHashRoute.ts";

const LABELS: Record<Route, string> = {
  varos: "Város",
  regio: "Régió",
};

type TabsProps = {
  current: Route;
};

/**
 * Navigáció a két oldal között. Sima linkek a hash-re, így a böngésző
 * vissza gombja és a könyvjelzők is működnek.
 */
export function Tabs({ current }: TabsProps) {
  return (
    <nav aria-label="Nézetek" className="flex gap-1 border-b border-slate-200">
      {(Object.keys(LABELS) as Route[]).map((route) => {
        const active = route === current;
        return (
          <a
            key={route}
            href={routeHref(route)}
            aria-current={active ? "page" : undefined}
            className={`-mb-px border-b-2 px-4 py-2 text-sm font-medium transition-colors ${
              active
                ? "border-sky-600 text-sky-700"
                : "border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800"
            }`}
          >
            {LABELS[route]}
          </a>
        );
      })}
    </nav>
  );
}
