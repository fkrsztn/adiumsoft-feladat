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
    <nav aria-label="Nézetek" className="flex gap-6">
      {(Object.keys(LABELS) as Route[]).map((route) => {
        const active = route === current;
        return (
          <a
            key={route}
            href={routeHref(route)}
            aria-current={active ? "page" : undefined}
            className={`font-display border-b-2 pb-1 text-xl font-medium tracking-wide uppercase transition-colors ${
              active
                ? "border-accent text-ink"
                : "border-transparent text-ink-muted hover:border-rule hover:text-ink"
            }`}
          >
            {LABELS[route]}
          </a>
        );
      })}
    </nav>
  );
}
