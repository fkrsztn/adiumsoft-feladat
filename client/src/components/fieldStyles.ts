/**
 * Közös Tailwind osztályok az űrlapelemekhez.
 *
 * A mezők és a gomb azonos, rögzített magasságúak (`h-10`): a böngésző a
 * választólistának és a dátummezőnek eltérő belső magasságot adna, így egy
 * sorban egyenetlenül állnának.
 */
const controlHeight = "h-10";

export const labelClass =
  "font-display block text-sm font-medium tracking-[0.12em] text-ink-muted uppercase";

export const inputClass = `mt-1 block w-full ${controlHeight} rounded-sm border border-ink/30 bg-white px-3 text-base leading-none text-ink focus:border-accent focus:ring-2 focus:ring-accent/25 focus:outline-none disabled:bg-paper-deep disabled:text-ink-faint`;

export const buttonClass = `font-display inline-flex ${controlHeight} items-center justify-center rounded-sm bg-accent px-6 text-lg leading-none font-medium tracking-wider text-white uppercase hover:bg-accent-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent disabled:cursor-not-allowed disabled:bg-ink-faint`;
