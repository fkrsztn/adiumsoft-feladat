import { Tabs } from "./components/Tabs.tsx";
import { UnitToggle } from "./components/UnitToggle.tsx";
import { useHashRoute } from "./hooks/useHashRoute.ts";
import { useUnitSystem } from "./hooks/useUnitSystem.ts";
import { CityPage } from "./pages/CityPage.tsx";
import { RegionPage } from "./pages/RegionPage.tsx";

export function App() {
  const route = useHashRoute();
  const [unitSystem, setUnitSystem] = useUnitSystem();

  return (
    <div className="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6">
      <header className="flex flex-wrap items-end justify-between gap-x-6 gap-y-1 border-b-2 border-ink pb-2">
        <h1 className="font-display text-4xl leading-none font-bold tracking-tight uppercase sm:text-5xl">
          Időjárási napló
        </h1>
        <p className="font-display text-sm tracking-[0.12em] text-ink-muted uppercase">
          HungaroMet · 10 állomás · 1901–2023
        </p>
      </header>

      <div className="flex flex-wrap items-center justify-between gap-4">
        <Tabs current={route} />
        <UnitToggle value={unitSystem} onChange={setUnitSystem} />
      </div>

      <main>
        {route === "varos" ? (
          <CityPage unitSystem={unitSystem} />
        ) : (
          <RegionPage unitSystem={unitSystem} />
        )}
      </main>
    </div>
  );
}
