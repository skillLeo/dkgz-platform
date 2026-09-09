# DKGZ — Deutsche KFZ-Gutachterzentrale

A nationwide German platform that puts a car owner in touch with a qualified
vehicle assessor. The customer answers two questions and leaves a telephone
number; DKGZ finds a partner whose service area covers that postal code, and the
first one to accept takes the job.

**No money moves through this system.** There is no payment gateway, no wallet
and no payout. The referral fee DKGZ charges its partners is recorded and
invoiced as a PDF; settling it happens outside the platform.

Laravel · Inertia 2 · Vue 3 · Tailwind 4 · MySQL — built to run on shared
hosting with no Redis, no queue daemon and no websockets.

---

## How a request travels

1. **The customer chooses an assessment.** The homepage offers the commonest one
   directly; everything else opens the request page, which lists them all with an
   explanation behind each. One more screen asks for postal code, name,
   telephone and e-mail. Nothing else — the assessor telephones and asks the
   rest.
2. **DKGZ matches it.** Partners are selected by postal-code coverage, by the
   services they offer and by whether they are currently available. Each is sent
   the job without the customer's contact details.
3. **A partner accepts.** The request closes for everyone else. Contact details
   are released only to the partner who accepted.
4. **The partner starts work.** Marking the job *In Bearbeitung* is the moment
   DKGZ has earned its fee: the commission is booked and a numbered invoice PDF
   is generated and e-mailed to the partner.
5. **The job is completed** by the partner, and the customer is invited to leave
   a review.

The fee is a fixed amount per assessment type, **snapshotted onto the job when
the partner accepts**. Changing the price list afterwards can never rewrite a
job already running.

If nobody accepts, or a partner hands the job back, the customer is told —
silence after handing over a telephone number is the worst outcome available.

---

## Requirements

| | |
|---|---|
| PHP | 8.3+ (8.4 in production) |
| Node | 20+ |
| Database | MySQL/MariaDB in production, SQLite in tests |
| Extensions | `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`, `bcmath` |

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build            # or: npm run dev
php artisan serve
```

`migrate --seed` runs `ProductionSeeder` (roles, permissions, settings, service
types, the German postal-code table, mail templates, every editable string, legal
pages) and then `DemoSeeder`, which refuses to run when `APP_ENV=production`.

For a live install, seed the production data only:

```bash
php artisan db:seed --class=ProductionSeeder --force
```

## Tests

```bash
php artisan test          # or: vendor/bin/pest --compact
```

839 tests covering the request flow end to end, matching and availability,
commission and VAT arithmetic, permissions and role boundaries, German grammar,
and the public pages. The suite runs against SQLite in memory and touches no
external service.

Several tests read Vue source files and assert on them. There is no JavaScript
test runner in this project, and a source assertion is the only way to hold a
front-end guarantee that has been broken before.

---

## Operating it

Almost nothing user-facing is hard-coded. The admin panel owns the words.

- **Seiteninhalte** — every string on every public page, addressed as
  `page.section.field`. A block can also be a **switch**, drawn as a toggle,
  which turns a section on or off without deleting the wording that fills it.
- **Leistungsarten** — the assessments, their order, their icons, their fee, and
  the explanation shown behind the *i* on the request form. The first in the list
  is the one the homepage leads with.
- **Einstellungen** — branding colours, contact details, SMTP, feature switches.
- **Test bookings** — set a five-digit code under *Funktionen →
  Postleitzahl für Testanfragen*. A request submitted with that code runs the
  whole flow and is marked as a test, but is never shown to a partner. Leave it
  empty to switch the feature off.

### German is a first-class concern

The copy is written once and reused for every assessment, so the articles in
front of a service name are bent to its gender: *zum Unfallgutachten* but *zur
Beweissicherung*, *für Ihr Gutachten* but *für Ihre Fahrzeugbewertung*. The
gender is stored per service and falls back to a suffix rule. An editable string
carries the article inside the placeholder — `{Ihren leistung}` — so the operator
writes it once in the masculine and every other form follows.

---

## Deployment

Full instructions live in [DEPLOYMENT.md](DEPLOYMENT.md). The shape of it:

- **`public/build` is committed on purpose.** The host has no npm, so assets are
  built locally and shipped.
- **No queue daemon.** Mail is drained by middleware riding on ordinary traffic,
  with a circuit breaker so a broken mail server cannot take the site down —
  which it once did.
- **Adding a content block needs the seeder.** `db:seed --class=ContentBlockSeeder
  --force` is safe on every run: it writes a block's value only when the block
  does not yet exist, so nothing an operator wrote is overwritten.

---

## Conventions

- **Comments say why, not what.** Where the code looks odd, the comment explains
  the failure that made it that way.
- **No arbitrary design values.** `DESIGN_TOKENS.md` governs every colour,
  radius, spacing step and duration.
- **Money is integer cents**, cast at the model boundary. Rates are frozen onto
  the row at the moment an invoice is issued, because a rate is a fact about a
  date.
- **Invoice numbers are consecutive and never reused**, and an issued number is
  never rewritten.

## Repository layout

```
app/            Actions, models, controllers, jobs, policies, support classes
resources/js/   Inertia pages, layouts, base components, domain components
resources/css/  The design tokens and the single stylesheet
database/       Migrations, factories, seeders
tests/Feature/  The suite
design-src/     The original design documents the build was made from
public/build/   Compiled assets, committed deliberately
```
