# Kliens

React 19 + TypeScript + Vite + Tailwind CSS felület az időjárási adatokhoz.

Fejlesztői cím: `http://localhost:5173`, prodban: `http://localhost:8300`.

## Mit tud

- Két oldal: **Város** (`#/varos`) és **Régió** (`#/regio`). Mindkettőn ki kell választani a várost vagy a régiót és az időszakot, az adatok a gombnyomásra töltődnek be.
- Táblázat napi bontásban: minimum, maximum, átlag, csapadék, mértékegységgel. A láblécben oszloponként a medián és a módusz.
- **°C / °F és mm / in váltó.** Az API mindig SI mértékegységben válaszol, az átváltás a kliensen történik. A választás újratöltés után is megmarad (`localStorage`).
- A hőmérséklet-cellák színe az értéket jelzi, a csapadéknál egy sáv mutatja a mennyiséget.

## Döntések

- **Hash alapú útvonalak (`#/varos`).** A prod nginx beállítása (`docker/`, nem módosítható) nem irányít vissza az `index.html`-re, így egy `/varos` cím frissítéskor 404-et adna.
- **Az időszak legfeljebb 366 nap**, ugyanúgy, mint az API-ban (indoklás: `api/README.md`). A kliens már küldés előtt ellenőrzi, hogy a felhasználó azonnal visszajelzést kapjon (`src/lib/dateRange.ts`).
- **API cím:** a `VITE_API_URL` környezeti változóból (`src/api/client.ts`). Fejlesztéskor a `docker-compose.yaml`, prodban a `docker/prod.Dockerfile` adja meg.

## Felépítés

```
src/
  api/          API hívások és a válaszok típusai
  pages/        a Város és a Régió oldal
  components/   űrlap, táblázat, fülek, mértékegység-váltó
  hooks/        útvonal, kérésállapot, mértékegység
  lib/          tiszta függvények: átváltás, formázás, dátumellenőrzés (Vitest tesztekkel)
```

## Parancsok

A `takehome/` mappából, futó `client-dev` konténerrel:

```sh
docker exec takehome-client-dev npm test            # Vitest tesztek
docker exec takehome-client-dev npm run lint        # ESLint
docker exec takehome-client-dev npm run typecheck   # TypeScript ellenőrzés
docker exec takehome-client-dev npm run build       # prod build
```

A `typecheck` script eredetileg `tsc --noEmit` volt, ami a projekt beállításai miatt egyetlen fájlt sem ellenőrzött; most `tsc -b`.
