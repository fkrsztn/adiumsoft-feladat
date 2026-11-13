import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
// A betűk a csomaggal érkeznek (nem külső szolgáltatásból), így internet
// nélkül is működnek. Csak a használt vastagságokat töltjük be.
import "@fontsource/barlow/400.css";
import "@fontsource/barlow/500.css";
import "@fontsource/barlow/600.css";
import "@fontsource/barlow-condensed/500.css";
import "@fontsource/barlow-condensed/700.css";
import "./index.css";
import { App } from "./App.tsx";

createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
