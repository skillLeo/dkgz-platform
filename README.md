# DKGZ — Deutsche KFZ-Gutachterzentrale

A nationwide German vehicle-assessor referral platform. A customer submits one
short form with no account; the system matches the request to assessors whose
service area covers that postal code and who are currently available; the first
to accept takes the assignment and it closes for everyone else. Contact details
stay hidden until acceptance. The assessor works the job outside the platform,
uploads the report and the invoice they issued, marks it complete and enters the
fee actually charged. DKGZ records a referral commission on that fee.

**No money moves through this system.** There is no payment gateway, no wallet
and no payout — the commission is a record, not a transaction.

Laravel · Inertia 2 · Vue 3 · Tailwind 4 · MySQL, built for shared hosting.

---

## Local setup

Requires PHP 8.3+, Composer and Node 20+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # production data + demo records
npm run build                     # or: npm run dev
php artisan serve
```

`migrate --seed` runs `ProductionSeeder` and then `DemoSeeder`. The demo seeder
refuses to run when `APP_ENV=production`.

### Seeders

| Seeder | What it writes |
|---|---|
| `ProductionSeeder` | roles, 44 permissions, all settings with German defaults, 8 service types, the German postal-code table, 19 mail templates, every content block with the copy from the design, 4 legal pages, 10 FAQs, one super-admin |
| `DemoSeeder` | 25 assessors across real German cities, 60 requests in mixed states, 30 assignments, 20 commissions across all four statuses, reviews and invitations |

```bash
php artisan db:seed --class=ProductionSeeder --force   # live installs
```

### Signing in locally

After `migrate --seed` the console prints a generated super-admin password for
`admin@dkgz.de`. Demo accounts all use `Gutachten2026!`:

| Account | Role |
|---|---|
| `admin@dkgz.test` | admin |
| `vermittlung@dkgz.test` | manager |
| `support@dkgz.test` | support |
| `redaktion@dkgz.test` | content_editor |
| `sv1@dkgz.test` … `sv25@dkgz.test` | assessor |

Staff sign in at `/admin/anmelden`, partners at `/anmelden`.

---

## Tests

```bash
php artisan test          # or: ./vendor/bin/pest
./vendor/bin/pint         # formatting
```

The suite covers, among other things:

- **first-accept-wins under concurrency** — two simultaneous accepts must
  produce exactly one assignment, guarded by a pessimistic row lock and, behind
  it, a unique index
- the matching rule in isolation: approved **and** available **and** the user
  active **and** in-area **and** offering that service, nothing else
- customer contact data absent from the payload before acceptance and present
  after, asserted against the raw response rather than only the props
- documents unreachable without authorisation
- commission arithmetic across a spread of fees, and rate snapshotting — editing
  the rate must never rewrite a historical record
- every admin route hit by every role, asserting 200 or 403
- German money, date and phone formatting identical in PHP and JavaScript, by
  running the Vue composable under Node against the same fixtures

---

## Layout

```
app/
├── Actions/          MatchRequestAction, AcceptAssignmentAction, CompleteAssignmentAction
├── Support/          Settings, Content, Branding, Formatter, Mailer, SafeStorage, Permissions
├── Policies/         one per model; routes are gated by permission, never by role
resources/js/
├── Layouts/          Public, Auth, Portal, Admin
├── Pages/            Public/ Auth/ Portal/ Admin/
├── Components/       Base/ Data/ Feedback/ Layout/ Domain/
└── Composables/      useGermanFormat, usePermissions, usePolling, useConfirm, …
design-src/           the imported design project, kept as the reference
```

`DESIGN_TOKENS.md` holds every colour, size and duration, extracted from
`design-src/DKGZ Design Foundations.dc.html`. No component uses an arbitrary
value.

---

## Notes that matter

- **Fonts are self-hosted.** German courts have held that pulling fonts from
  Google's CDN unlawfully transmits visitor IP addresses, so IBM Plex ships from
  `@fontsource` and the build contains zero requests to any Google domain.
- **Money is integer cents everywhere**, cast through `MoneyCast`. Never a float.
- **The commission rate is never hardcoded.** It is read from
  `settings.business.commission_rate` and snapshotted onto each commission row.
- **Almost everything is admin-editable** without a deploy: page copy, legal
  pages, FAQs, logos, every colour token, SMTP credentials, all mail templates,
  service types and the business rules.

See `DEPLOYMENT.md` for the server, `HANDOVER.md` for the client-facing guide,
`BUILD_SPEC.md` for the binding rules and `DECISIONS.md` for judgement calls.
