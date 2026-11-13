import { useId } from "react";
import { FIRST_AVAILABLE_DATE, LAST_AVAILABLE_DATE } from "../lib/dateRange.ts";
import { inputClass, labelClass } from "./fieldStyles.ts";

type DateRangeFieldsProps = {
  startDate: string;
  endDate: string;
  onStartDateChange: (value: string) => void;
  onEndDateChange: (value: string) => void;
};

/**
 * Kezdő és záró dátum mező. A dátumválasztó csak az adatok időszakán belül
 * enged választani; a teljes ellenőrzés a `validateDateRange`-ben van.
 */
export function DateRangeFields({
  startDate,
  endDate,
  onStartDateChange,
  onEndDateChange,
}: DateRangeFieldsProps) {
  const startId = useId();
  const endId = useId();

  return (
    <>
      <div>
        <label htmlFor={startId} className={labelClass}>
          Kezdő dátum
        </label>
        <input
          id={startId}
          type="date"
          required
          min={FIRST_AVAILABLE_DATE}
          max={LAST_AVAILABLE_DATE}
          value={startDate}
          onChange={(event) => onStartDateChange(event.target.value)}
          className={inputClass}
        />
      </div>
      <div>
        <label htmlFor={endId} className={labelClass}>
          Záró dátum
        </label>
        <input
          id={endId}
          type="date"
          required
          min={FIRST_AVAILABLE_DATE}
          max={LAST_AVAILABLE_DATE}
          value={endDate}
          onChange={(event) => onEndDateChange(event.target.value)}
          className={inputClass}
        />
      </div>
    </>
  );
}
