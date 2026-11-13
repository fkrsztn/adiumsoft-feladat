import { useCallback, useState } from "react";
import type { UnitSystem } from "../lib/units.ts";

const STORAGE_KEY = "unitSystem";

/**
 * A választott mértékegység-rendszer. A böngészőben megjegyezzük, így
 * újratöltés után is megmarad. Ha a tárhely nem elérhető (pl. privát mód),
 * csendben az alapértelmezettel működik.
 */
export function useUnitSystem(): [UnitSystem, (system: UnitSystem) => void] {
  const [system, setSystem] = useState<UnitSystem>(readStored);

  const update = useCallback((next: UnitSystem) => {
    setSystem(next);
    try {
      window.localStorage.setItem(STORAGE_KEY, next);
    } catch {
      // A megjegyzés csak kényelmi funkció; hiba esetén nem mentjük.
    }
  }, []);

  return [system, update];
}

function readStored(): UnitSystem {
  try {
    return window.localStorage.getItem(STORAGE_KEY) === "imperial"
      ? "imperial"
      : "metric";
  } catch {
    return "metric";
  }
}
