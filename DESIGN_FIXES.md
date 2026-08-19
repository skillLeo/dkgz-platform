# Design Fixes

Problems found in the imported design project, and what was done about them.

## F-01 · Design files load fonts from the Google CDN — removed
Every one of the nine `.dc.html` files contains:

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans…">

That is exactly the pattern German courts have ruled unlawful, and the brief
forbids it explicitly. The design files are kept as-received for reference; the
production build self-hosts IBM Plex Sans and IBM Plex Mono through `@fontsource`.
The scaffold's `vite.config.js` also shipped a `bunny('Instrument Sans')` loader —
wrong family *and* a third-party CDN — which has been removed.

## F-02 · Scaffold shipped the wrong typeface
`resources/css/app.css` defined `--font-sans: 'Instrument Sans'`. The design system
is IBM Plex Sans throughout. Replaced.

## F-03 · `uploads/*.png` could not be retrieved in full
`pasted-1787034204088-0.png` (2294×1490) and `pasted-1787034263837-0.png`
(2296×1590) exceed the design API's hard 256 KiB per-file response cap, which has
no range/offset parameter. Roughly the first 184 KB of each was recovered and the
PNG chunk stream was repaired so both files decode and their upper portions render.
They are reference screenshots pasted by the client, not a source of design tokens
— the complete Foundations file supplies those — so the build is unaffected.
If the full originals are needed, they must be exported from the design project
by hand.

## F-04 · Contradictory minimum font size
See DECISIONS.md D-03.
