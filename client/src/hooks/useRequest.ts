import { useCallback, useEffect, useRef, useState } from "react";
import { ApiError } from "../api/client.ts";

export type RequestState<T> =
  | { status: "idle" }
  | { status: "loading" }
  | { status: "success"; data: T }
  | { status: "error"; message: string };

/**
 * Gombnyomásra induló kérés állapota (betöltés, siker, hiba).
 *
 * Új kérés indításakor az előzőt megszakítja, így egy lassabban érkező régi
 * válasz nem írhatja felül az újat. A komponens eltűnésekor is megszakít.
 */
export function useRequest<T>(): {
  state: RequestState<T>;
  run: (request: (signal: AbortSignal) => Promise<T>) => void;
} {
  const [state, setState] = useState<RequestState<T>>({ status: "idle" });
  const controllerRef = useRef<AbortController | null>(null);

  useEffect(() => () => controllerRef.current?.abort(), []);

  const run = useCallback((request: (signal: AbortSignal) => Promise<T>) => {
    controllerRef.current?.abort();
    const controller = new AbortController();
    controllerRef.current = controller;

    setState({ status: "loading" });
    request(controller.signal).then(
      (data) => {
        if (!controller.signal.aborted) {
          setState({ status: "success", data });
        }
      },
      (error: unknown) => {
        if (!controller.signal.aborted) {
          setState({ status: "error", message: errorMessage(error) });
        }
      },
    );
  }, []);

  return { state, run };
}

/**
 * Adat betöltése a komponens megjelenésekor (pl. a városlista).
 */
export function useLoad<T>(
  load: (signal: AbortSignal) => Promise<T>,
): RequestState<T> {
  const { state, run } = useRequest<T>();

  useEffect(() => {
    run(load);
  }, [run, load]);

  return state.status === "idle" ? { status: "loading" } : state;
}

function errorMessage(error: unknown): string {
  return error instanceof ApiError
    ? error.message
    : "Váratlan hiba történt. Próbáld újra.";
}
