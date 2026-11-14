import { UNIT_SYSTEM_LABELS, type UnitSystem } from "../lib/units.ts";

type UnitToggleProps = {
  value: UnitSystem;
  onChange: (system: UnitSystem) => void;
};

/**
 * Mértékegység-váltó (SI ↔ angolszász). Rádiógombokból áll, így
 * billentyűzettel és képernyőolvasóval is kezelhető.
 *
 * A látható „Mértékegység” felirat keskeny képernyőn rejtve van, mert ott a
 * fülek mellett nem fér el; a képernyőolvasó a `legend`-et mindig megkapja.
 */
export function UnitToggle({ value, onChange }: UnitToggleProps) {
  return (
    <fieldset className="flex items-center gap-3">
      <legend className="sr-only">Mértékegység</legend>
      <span
        aria-hidden="true"
        className="hidden font-display text-sm tracking-[0.12em] text-ink-muted uppercase sm:inline"
      >
        Mértékegység
      </span>
      <div className="flex border border-ink">
        {(Object.keys(UNIT_SYSTEM_LABELS) as UnitSystem[]).map((system) => (
          <label
            key={system}
            className="cursor-pointer px-3 py-1 text-sm font-medium text-ink has-checked:bg-ink has-checked:text-paper has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-accent"
          >
            <input
              type="radio"
              name="unitSystem"
              value={system}
              checked={value === system}
              onChange={() => onChange(system)}
              className="sr-only"
            />
            {UNIT_SYSTEM_LABELS[system]}
          </label>
        ))}
      </div>
    </fieldset>
  );
}
