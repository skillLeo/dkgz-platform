# Decisions

Ambiguities resolved during the build, with the reasoning.

## D-01 · Accent colour is gold `#B08A2E`
The written brief says only "very selective use of an accent color". The design
project settles it: `--dkgz-accent: #B08A2E` is declared in the `:root` of six of
the nine design files, with `#8C6D22`, `#A28B5B` and `#14294A` offered as the
alternative options in the `accent` prop. Gold it is, exposed as a runtime CSS
custom property so the admin can switch it without a rebuild.

## D-02 · Colour token names follow Foundations, not Tailwind convention
Foundations §01 names the ramp `navy-900 · navy-800 · navy-700 · navy-600 ·
navy-500 · navy-100` and `gray-50 … gray-800`, with gaps (no navy-400, no
gray-500/700/900). The gaps are kept rather than filled, so a token name in the
code always means the same swatch the designer named.

## D-03 · 11px is permitted for the mobile tab-bar label only
Spec §11 requires an "11px label" on the bottom tab bar and, four bullets later,
"No text below 12px". Direct contradiction inside one section. Resolved in favour
of the more specific instruction: `--text-tab: 11px` exists and is used on the tab
bar and nowhere else. Every other string is ≥ 12.5px. The design files agree —
`font-size:11px` appears 28 times, all in the mobile tab bar.

## D-04 · Radius ceiling is 5px, not 6px
The brief allows "4–6px" and §3 caps at 6px. The design project actually uses
`3px` (494×) for controls and `5px` (138×) for cards, and nothing between 6px and
10px. The theme therefore ships `--radius-xs/sm/card` = 2/3/5px. The 10px and 13px
values in the source are the circular seal mark, not rectangular surfaces.

## D-05 · Body base is 15px and the workhorse size is 13.5px
Unusual, but unambiguous in the source: `font-size:15px` 485×, `13.5px` 1051×.
13.5px carries tables, labels and metadata; 15px carries prose. Both are tokens.

## D-06 · 19 e-mail templates, not 18
BUILD_SPEC lists 18 and folds the review link into "Auftrag abgeschlossen".
"DKGZ E-Mail-Vorlagen.dc.html" instead ships a separate template 05
"Bewertungsanfrage", triggered "3 Tage nach Abschluss" — a different recipient
moment with its own 30-day token. Both are seeded: the completion mail carries
the documents, the review request follows on the configured delay. The design
file wins because it is the more specific source and the review flow needs the
delayed send. All 18 named in the spec are present; this is a superset.

## D-07 · Assignment documents live on a dedicated `private` disk
`config/filesystems.php` already had a `local` disk rooted at
`storage/app/private`. Rather than share it with anything else Laravel writes
there, assignment documents and commission invoices get their own `private`
disk rooted at `storage/app/private/dkgz` with `serve => false`, so no route
can ever stream them by accident.
