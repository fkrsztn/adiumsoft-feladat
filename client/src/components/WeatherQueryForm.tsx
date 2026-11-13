import { useId, useState, type FormEvent } from "react";
import type { RequestState } from "../hooks/useRequest.ts";
import { validateDateRange } from "../lib/dateRange.ts";
import { DateRangeFields } from "./DateRangeFields.tsx";
import { buttonClass, inputClass, labelClass } from "./fieldStyles.ts";
import { StatusMessage } from "./StatusMessage.tsx";

export type SelectOption = {
  value: string;
  label: string;
};

export type WeatherQuery = {
  selection: string;
  startDate: string;
  endDate: string;
};

const DEFAULT_START_DATE = "2023-07-01";
const DEFAULT_END_DATE = "2023-07-31";

type WeatherQueryFormProps = {
  /** A választómező felirata, pl. „Város”. */
  selectLabel: string;
  placeholder: string;
  /** Üzenet, ha a felhasználó nem választott, pl. „Válassz várost.” */
  missingSelectionMessage: string;
  options: RequestState<SelectOption[]>;
  /** Folyamatban van-e a lekérdezés (a gomb ilyenkor le van tiltva). */
  loading: boolean;
  onSubmit: (query: WeatherQuery) => void;
};

/**
 * Lekérdező űrlap: egy választómező (város vagy régió), kezdő és záró
 * dátum, Lekérdezés gomb. Beküldés előtt ellenőriz, és csak érvényes
 * lekérdezést ad tovább. A város- és a régióoldal is ezt használja.
 */
export function WeatherQueryForm({
  selectLabel,
  placeholder,
  missingSelectionMessage,
  options,
  loading,
  onSubmit,
}: WeatherQueryFormProps) {
  const selectId = useId();
  const [selection, setSelection] = useState("");
  const [startDate, setStartDate] = useState(DEFAULT_START_DATE);
  const [endDate, setEndDate] = useState(DEFAULT_END_DATE);
  const [validationError, setValidationError] = useState<string | null>(null);

  const optionsReady = options.status === "success";

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const error =
      selection === ""
        ? missingSelectionMessage
        : validateDateRange(startDate, endDate);
    setValidationError(error);
    if (error === null) {
      onSubmit({ selection, startDate, endDate });
    }
  };

  return (
    <div className="space-y-3">
      <form
        onSubmit={handleSubmit}
        noValidate
        className="grid gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end"
      >
        <div>
          <label htmlFor={selectId} className={labelClass}>
            {selectLabel}
          </label>
          <select
            id={selectId}
            required
            value={selection}
            onChange={(event) => setSelection(event.target.value)}
            disabled={!optionsReady}
            className={inputClass}
          >
            <option value="">
              {options.status === "loading" ? "Betöltés…" : placeholder}
            </option>
            {optionsReady &&
              options.data.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
          </select>
        </div>

        <DateRangeFields
          startDate={startDate}
          endDate={endDate}
          onStartDateChange={setStartDate}
          onEndDateChange={setEndDate}
        />

        <button
          type="submit"
          disabled={loading || !optionsReady}
          className={buttonClass}
        >
          {loading ? "Betöltés…" : "Lekérdezés"}
        </button>
      </form>

      {options.status === "error" && (
        <StatusMessage kind="error">
          A választólista nem tölthető be: {options.message}
        </StatusMessage>
      )}
      {validationError !== null && (
        <StatusMessage kind="error">{validationError}</StatusMessage>
      )}
    </div>
  );
}
