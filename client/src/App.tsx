import { Tabs } from "./components/Tabs.tsx";
import { useHashRoute } from "./hooks/useHashRoute.ts";
import { CityPage } from "./pages/CityPage.tsx";
import { RegionPage } from "./pages/RegionPage.tsx";

export function App() {
  const route = useHashRoute();

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <main className="mx-auto max-w-5xl space-y-6 px-4 py-8">
        <header className="space-y-1">
          <h1 className="text-2xl font-bold tracking-tight">
            Időjárási adatok
          </h1>
          <p className="text-sm text-slate-600">
            Napi mérések tíz magyar mérőállomásról, 1901 és 2023 között (forrás:
            HungaroMet).
          </p>
        </header>

        <Tabs current={route} />

        {route === "varos" ? <CityPage /> : <RegionPage />}
      </main>
    </div>
  );
}
