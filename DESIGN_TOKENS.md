# DKGZ Design Tokens — extracted from `design-src/DKGZ Design Foundations.dc.html`

Authoritative. Every value below was read out of the design project, not invented.
Frequencies are occurrences across all 9 `.dc.html` files.

## Colour — names taken verbatim from Foundations section 01 "Farbe"

| Token      | Hex       | Uses | Role |
|------------|-----------|------|------|
| navy-900   | `#0A1628` | 25   | Auth left panel, e-mail header band, mobile top bar |
| navy-800   | `#0E1E36` | 12   | Button `:active` |
| navy-700   | `#14294A` | 935  | **Primary brand.** Buttons, headings, links, seal |
| navy-600   | `#1C3A66` | 2    | Deep accent line |
| navy-500   | `#27508B` | 113  | Link hover, focus ring, button hover |
| navy-100   | `#E8EDF5` | 76   | `::selection`, tint fills |
| gray-50    | `#F8F9FB` | 116  | Subtle section background |
| gray-100   | `#F1F3F7` | 602  | Page background, table row divider |
| gray-200   | `#E3E7EE` | 638  | Borders, hairlines |
| gray-300   | `#CBD2DD` | 334  | Input borders |
| gray-400   | `#9AA5B4` | 446  | Placeholder, meta text |
| gray-600   | `#5B687C` | 840  | Muted body, labels |
| gray-800   | `#2E3A4C` | 783  | Body text |
| white      | `#FFFFFF` | 522  | Surfaces |
| accent     | `#B08A2E` | 121  | Gold. Eyebrow text, 24×2px rule, seal ring |
| accent-alt | `#8C6D22` / `#A28B5B` | 7 each | Configurable accent options |
| danger     | `#A32318` | 113  | Field error, required marker |
| danger-900 | `#8A1D14` | 3    | Danger pressed |
| success    | `#1B6E48` | 63   | Strength meter, positive status |
| success-50 | `#E8F7EE` | 2    | Success tint |
| warning    | `#9C6F15` | 57   | Caps-lock hint, warn status |
| flag       | `#000000` `#DD0000` `#FFCE00` | 7 each | German flag rule, 40×3px |

Accent is exposed as `--dkgz-accent` and is admin-overridable at runtime.

## Type — IBM Plex Sans / IBM Plex Mono, self-hosted (GDPR)

| px    | Uses | Role |
|-------|------|------|
| 52    | 3    | Display |
| 40    | 6    | Public page H1 |
| 30    | 47   | H2 / auth headline |
| 26    | 8    | Auth shell wordmark |
| 24    | 16   | E-mail H1, mobile H2 |
| 22    | 69   | H3, section heading, wordmark |
| 20    | 17   | Mobile headline, ref number |
| 19    | 6    | E-mail wordmark |
| 17    | 99   | Lead paragraph |
| 15    | 485  | **Body base** |
| 13.5  | 1051 | **Most common.** Table cells, labels, meta |
| 12.5  | 146  | Helper text, footnotes |
| 11.5  | 640  | Eyebrow (uppercase, `0.09em`), mono annotation |
| 11    | 28   | Mobile tab-bar label |
| 9.5 / 9 / 8 / 7 / 6.5 / 6 | — | Logo lockup only |

Letter-spacing: `0.09em` uppercase eyebrow · `0.14em`/`0.12em` logo subtitle ·
`-0.012em` → `-0.028em` on headings, tighter as size grows.
Line-height: `1.6` body · `1.75` legal long-form · `1.2` headings · `1.12` display.
All numerics: `font-variant-numeric: tabular-nums`.

## Geometry & depth — Foundations section 04 "Geometrie & Tiefe"

Radius: `2px` small · **`3px` default** (494 uses: buttons, inputs, code cells) ·
**`5px` cards** (138 uses) · `10px`/`13px` reserved for the circular seal only.
Never exceeds 5px on a rectangular surface. Tables are square.

Three shadow levels, and only three:
- `shadow-1` `0 1px 2px rgba(10,22,40,0.05)`
- `shadow-2` `0 4px 12px rgba(10,22,40,0.10)`
- `shadow-3` `0 16px 48px rgba(10,22,40,0.16)`
- plus one inset marker: `inset 2px 0 0 #14294A` (active sidebar row)

## Control sizing

Input / button height `46px` (auth, portal forms) and `40px` (compact public form),
`44px` primary public CTA. Code cell `52×56px`. Rating button `48×48px`.
Touch targets ≥ 44px. Content width `1240px` (design uses `max-width:1240px`),
form column capped at `420px`, legal measure `68ch`, body measure `62–78ch`.

## Motion — Foundations section 10 "Bewegung"

`140ms cubic-bezier(0.4,0,0.2,1)` hover · `120ms` focus ring · `160ms` strength meter ·
`360ms` auth right-panel entrance (`dkgz-rise`: opacity 0→1, translateY 8→0) ·
`420ms` hero entrance (`dkgz-enter`: translateY 12→0) · `700ms linear infinite` spinner.
No scroll-triggered reveals, no parallax, no stagger.
