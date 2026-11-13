import type { ReactNode } from "react";

type StatusMessageProps = {
  kind: "info" | "error";
  children: ReactNode;
};

/**
 * Állapotüzenet (betöltés, üres eredmény, hiba). A hiba `role="alert"`, így
 * a képernyőolvasó azonnal felolvassa.
 */
export function StatusMessage({ kind, children }: StatusMessageProps) {
  if (kind === "error") {
    return (
      <p
        role="alert"
        className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
      >
        {children}
      </p>
    );
  }
  return (
    <p
      role="status"
      className="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
    >
      {children}
    </p>
  );
}
