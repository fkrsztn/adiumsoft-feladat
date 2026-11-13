import type { ReactNode } from "react";

type StatusMessageProps = {
  kind: "info" | "error";
  children: ReactNode;
};

/**
 * Állapotüzenet (útmutató, betöltés, üres eredmény, hiba). A hiba
 * `role="alert"`, így a képernyőolvasó azonnal felolvassa.
 */
export function StatusMessage({ kind, children }: StatusMessageProps) {
  if (kind === "error") {
    return (
      <p
        role="alert"
        className="rounded-sm border border-danger/30 bg-danger-soft px-4 py-3 text-danger"
      >
        {children}
      </p>
    );
  }
  return (
    <p
      role="status"
      className="rounded-sm border border-rule px-4 py-3 text-ink-muted"
    >
      {children}
    </p>
  );
}
