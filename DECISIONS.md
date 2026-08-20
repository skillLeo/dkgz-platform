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

## D-11 · Liability cover as a matching criterion (switchable)

The client specified four matching conditions: approved, available, area covers
the postal code, service type offered. Valid professional liability cover is a
**fifth** condition this build adds.

It is added because the account-suspension screen already tells partners their
cover expired, and matching an uninsured assessor is the one compliance failure
this business cannot absorb. It is a **setting**, not a hard rule, because it
goes beyond the brief:

`business.require_valid_liability_cover` — default **true**.

- **true** — a partner whose dated cover has all lapsed is removed from matching.
- **false** — cover is still tracked, still shown, and reminders still go out;
  it simply does not affect matching.

Partners with no dated cover on file are never excluded under either setting:
the date field was added after some partners had already registered, and
excluding everyone who predates a column would empty the network overnight.

## D-12 · Bank details are opt-in

The platform processes no payments and never pays a partner, so storing an IBAN
has no operational purpose by default and is a GDPR liability. Collection is
therefore behind `features.collect_bank_details`, default **false**. When off,
the Bankverbindung tab does not exist and no IBAN is stored. `dkgz:purge-bank-details`
clears anything already collected.

## D-13 · Postal codes: format only (supersedes the existence check)

Client change request, section 14. `ExistingPostalCode` checked every code
against the seeded table, which holds ~179 of Germany's ~8,200 codes — so real
addresses were rejected. Replaced by `GermanPostalCode`: five digits, nothing
more.

The table stays, purely to auto-fill a city. The lookup endpoint now answers 200
with a null city for an unknown code instead of 404, so the field simply stops
offering one. A wrong-but-valid code surfaces as a request nobody covers, which
the attention queue already reports; a rejected real address just loses the
customer.

## D-14 · Certification and liability proofs are optional at registration

Client change request, section 14. Zertifizierungsstelle and -nummer are a pair:
supply one and the other is required, supply neither and nothing is validated.
Both proof uploads are optional. A partner who never supplied a liability date
is not treated as lapsed — only a supplied date that has passed triggers the
warning system from the previous pass.

## D-15 · No acceptance deadline (supersedes D-… deadline architecture)

Client change request, section 15. A matched request now stays open until a
partner accepts it or an administrator closes it by hand. Removed:
`accept_deadline_at`, the `expired` status, the 15-minute expiry sweep, the
deadline reminder preference, `business.request_expiry_hours`, and every Frist
display.

`unanswered` survives — every matched partner actively declining is still a real
signal — but it is no longer time-based and no longer a dead end: a partner
approved in that area later can be matched to it.

The customer notification now fires on manual closure, on all-declined, or on
zero-match. Never on a timer.

Manual closure requires a reason, is recorded in the activity log and the
request's internal notes, and gates on `requests.reassign` rather than plain
viewing, since it ends the request for everyone it was sent to.
