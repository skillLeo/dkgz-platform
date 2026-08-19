# DKGZ BUILD SPEC

Authority for the rest of the build and for every future session.

═══════════════════════════════════════════════════════════════
PART C — REMAINING BUILD ORDER
═══════════════════════════════════════════════════════════════

 4. Models, MoneyCast, relationships, factories for all 27 tables.
 5. Roles, permissions, policies, middleware, production + demo seeders.
 6. Settings + content_blocks architecture, DynamicConfigServiceProvider.
 7. Base component library from DESIGN_TOKENS.md.
 8. Auth shell + all 11 auth screens with every state.
 9. Public site: homepage first, then all remaining public pages.
10. Request submission + matching engine + first-accept-wins + concurrency test.
11. Assessor portal, every screen.
12. Assignment workspace, uploads, completion, commission calculation.
13. Admin panel, every screen including the matching trail.
14. Content management, branding, integrations, email template editor.
15. All 18 emails, queued, logged.
16. Review and rating flow.
17. Mobile app shell across portal, admin and public.
18. Full Pest suite green.
19. Accessibility + responsive pass at 320/360/390/414/768/1024/1280/1440.
20. Deployment artefacts and German HANDOVER.md.

═══════════════════════════════════════════════════════════════
PART D — CRITICAL IMPLEMENTATION RULES (condensed, binding)
═══════════════════════════════════════════════════════════════

MONEY: every monetary value stored as unsignedBigInteger CENTS via MoneyCast.
Never float. Output format `1.275,00 €` identically from PHP Formatter helper
and Vue useGermanFormat composable. Dates `17.08.2026`, datetime adds
`, 14:32 Uhr`.

FIRST-ACCEPT-WINS — the most important code in the project:
  DB::transaction with ServiceRequest::whereKey($id)->lockForUpdate()->firstOrFail();
  abort if status !== 'matched'; create Assignment; set request to 'assigned';
  mark the winning request_match 'accepted'; bulk-update all other pending
  matches to 'closed'. Catch the UNIQUE violation on
  assignments.service_request_id as the second line of defence. The loser sees
  the German message "Dieser Auftrag wurde bereits von einem anderen
  Sachverständigen übernommen." — never an error page.
  MANDATORY TEST: two simultaneous accepts produce exactly one assignment.

MATCHING RULE — exactly this and nothing more: approval_status='approved' AND
is_available=true AND user.is_active=true AND the request postal code falls
numerically inside one of the assessor's service area ranges AND the assessor
offers that service_type. No distance, no ranking, no scoring, no
prioritisation. Zero matches → status stays 'new', flagged on the admin
dashboard as "Nicht vermittelt", admins notified. Never silently dropped.

COMMISSION: rate from settings key `business.commission_rate` default 15.00,
never hardcoded. On completion, snapshot the rate onto the commissions row so
historical records never change. commission_cents = (int) round(fee_cents *
rate_percent / 100). Fee min 5000 cents, max 5000000 cents, values above
1000000 flagged for admin review. Completion BLOCKED server-side unless both
the report and the customer invoice are uploaded. NO payment gateway, NO
Stripe, NO wallet, NO payouts anywhere.

PRIVACY: customer_name, customer_phone, customer_email stripped from the
Inertia payload at the API-resource layer for any assessor who has not accepted
that assignment. Never masked only in Vue. Assignment documents on a PRIVATE
disk, served solely through a controller that verifies ownership or
`assignments.view`.

ROLES: single users table, Spatie on the web guard, assessor = user with the
assessor role plus an assessors profile row. Roles super_admin (undeletable),
admin, manager, support, content_editor, assessor. A Policy per model; routes
gated by permission, never by role name. Permissions shared to Inertia for a
`can()` composable, backend always enforcing independently.

SHARED HOSTING: cache/session/queue on database. No Redis, no supervisor, no
websockets, no Octane, no Horizon. Queue via scheduler running
`queue:work --stop-when-empty --max-time=55` every minute with
withoutOverlapping(). In-portal notifications by 45-second polling. Chunk every
query — assume 128M memory. Commit public/build. league/csv for exports.

VISUAL LAW: DESIGN_TOKENS.md governs every value. No arbitrary Tailwind values —
no text-[17px], no #hex inline. No gradients, no glows, no glassmorphism, no
blur except the session-expired modal backdrop. Radius 3px controls / 5px cards
/ 0px tables. Icons lucide-vue-next only. ZERO emoji in any string. ZERO English
in any user-facing string. Tabular-nums on every numeric column. Motion only:
140ms hover, 420ms one-time hero entrance, 180ms disclosure, 200ms step
transition, plus the single looping pulse on the pending-approval timeline node.

MOBILE (below 768px): fixed bottom tab bar 64px + safe-area with 24px icons and
11px labels, active navy-700 with a 2px top indicator; fixed 56px top app bar
with back chevron on detail screens; "Mehr" full-screen sheet; tables become
stacked cards; filters in a bottom sheet with "Anwenden"; primary actions in a
fixed bottom action bar above the tab bar; 44×44px minimum touch targets; no
hover-only interactions; modals become full-screen sheets sliding 240ms; web
app manifest, apple-touch-icon, theme-color navy-900, viewport-fit=cover. No
horizontal scroll at 320px.

VALIDATION: PLZ exactly 5 digits resolved against a seeded German postal_codes
table for auto city fill; phone via laravel-phone region DE; VAT
/^DE[0-9]{9}$/ with checksum; German plate and 17-char VIN patterns; vehicle
year 1900..now+1; uploads validated by real MIME content not extension, EXIF
stripped, images re-encoded through Intervention; report/invoice max 10MB,
request images max 5 files 5MB. All messages German real sentences in
lang/de/validation.php. Every form is a dedicated FormRequest with a real
authorize().

SECURITY: rate limits login 5/min per IP+email, request 5/min, reset 3/min,
contact 3/min. Honeypot plus minimum-time-to-submit, no third-party captcha.
Signed URLs for review and invitation links. Passwords min 10 chars mixed case
number symbol plus the compromised-password rule. Optional TOTP 2FA for admins.
Security headers middleware. Spatie activity log on every model that matters.
Soft deletes with admin restore. GDPR consent timestamp on the request form, a
retention command anonymising old requests, and an admin data export by email
address.

ADMIN-EDITABLE LAYER: every public string from content_blocks; legal pages in
`pages`; FAQs reorderable; branding (logos, favicon, seal, and an override for
every colour token injected as CSS custom properties at runtime so a colour
change rethemes without a rebuild); contact and company data; SMTP host/port/
encryption/user/password ENCRYPTED at rest, applied at runtime by
DynamicConfigServiceProvider cached 60 minutes and busted on save, with a
"Testmail senden" button that sends a real mail and prints the real SMTP error
inline; all 18 email templates editable with a variable reference panel and
live preview; service types CRUD; business settings including commission rate,
request expiry, review redirect URL and minimum public-redirect rating
(default 8); feature toggles for self-registration, invitations, review flow,
image uploads, maintenance mode.

EMAILS (all queued, DB-driven with Blade fallback, table-based inline-CSS HTML
with plain-text alternative, 600px, logged to email_logs, 3 retries then
surfaced in admin): Anfrage eingegangen · Neue Anfrage in Ihrem Gebiet ·
Auftrag vergeben · Auftrag bestätigt · Auftrag abgeschlossen · Bewertung
erhalten · Registrierung eingegangen · Neue Registrierung zur Prüfung ·
Registrierung freigegeben · Registrierung abgelehnt · Konto gesperrt ·
Einladung zur Partnerschaft · Passwort zurücksetzen · E-Mail bestätigen ·
Provisionsabrechnung · Keine Sachverständigen gefunden · Kontaktanfrage ·
Testmail.

SEEDERS: production seeder (roles, permissions, all settings with German
defaults, 8 service types Haftpflichtgutachten/Kaskogutachten/Unfallgutachten/
Wertgutachten/Oldtimergutachten/Gebrauchtwagen-Check/Reparaturbestätigung/
Beweissicherung, 18 email templates, all content blocks with the exact German
copy from the design files, 4 legal pages, 10 FAQs, German PLZ table, one
super_admin). Separate demo seeder never run in production: 25 assessors across
real German cities, 60 requests in mixed states, 30 assignments, 20 commissions,
activity history.

═══════════════════════════════════════════════════════════════
PART E — ROUTES (build every one)
═══════════════════════════════════════════════════════════════

PUBLIC: / · /leistungen · /leistungen/{slug} · /ablauf · /ueber-uns ·
/fuer-sachverstaendige · /kontakt (GET+POST) · /anfrage (GET+POST, throttled) ·
/anfrage/bestaetigung/{reference} · /bewertung/{token} (GET+POST) ·
/bewertung/{token}/danke · /impressum · /datenschutz · /agb · /widerruf ·
/sitemap.xml · /robots.txt · 404/419/500/503 pages in the design language.

AUTH: /anmelden · /abmelden · /passwort-vergessen ·
/passwort-zuruecksetzen/{token} · /registrieren (4 steps) ·
/registrieren/schritt/{n} autosave · /email-bestaetigen/{token} ·
/email-bestaetigen/erneut · /einladung/{token} · /pruefung-laeuft ·
/registrierung-abgelehnt · /konto-gesperrt · /admin/anmelden ·
/admin/zwei-faktor.

PORTAL (/portal, auth + role:assessor + approved): dashboard · /anfragen ·
/anfragen/{id} · POST annehmen · POST ablehnen · /auftraege · /auftraege/{id} ·
POST status · POST dokumente · DELETE dokumente/{doc} · GET dokumente/{doc}/
download · POST abschliessen · /abgelehnt · /provisionen · /einsatzgebiet ·
/leistungen · /verfuegbarkeit · /profil · /einstellungen ·
/benachrichtigungen.

ADMIN (/admin, permission-gated per route): dashboard · /anfragen + /{id} with
full matching trail · POST erneut-vermitteln · /auftraege + /{id} ·
/sachverstaendige + /{id} · POST freigeben|ablehnen|sperren|entsperren ·
/einladungen + create/resend/revoke · /provisionen + /{id} + settle/waive/
invoice/export · /leistungsarten · /inhalte · /seiten · /faq ·
/email-vorlagen + /{key}/vorschau + /{key}/test · /branding · /integrationen +
/smtp-test · /einstellungen · /benutzer · /rollen · /protokoll · /system.

═══════════════════════════════════════════════════════════════
PART F — ACCEPTANCE CHECKLIST. YOU ARE NOT DONE UNTIL ALL PASS.
═══════════════════════════════════════════════════════════════

[ ] Every screen in the design project exists as a Vue page and matches it
[ ] Zero arbitrary Tailwind values; every value references a token
[ ] Zero requests to any Google or CDN font domain in the built output
[ ] Zero emoji in any UI string
[ ] Zero English in any user-facing string
[ ] Money renders 1.275,00 € identically server-side and client-side
[ ] Concurrency test passes: two simultaneous accepts, exactly one assignment
[ ] Customer contact data provably hidden before acceptance, visible after
[ ] Assignment documents unreachable without authorisation (test proves 403)
[ ] Commission rate read from settings and snapshotted onto the record
[ ] Every admin route permission-gated, tested per role
[ ] Page text, logo, colours, SMTP and email templates all editable from admin
[ ] Testmail button sends a real email and surfaces the real SMTP error
[ ] Mobile bottom nav, top app bar and sheets working on portal and admin
[ ] No horizontal scroll at 320px on any page
[ ] php artisan test fully green
[ ] npx vite build succeeds with no warnings
[ ] ./vendor/bin/pint clean
[ ] migrate:fresh --seed produces a working browsable platform
[ ] DEPLOYMENT.md (both hosting layouts), .htaccess ×2, deploy.sh, cron line,
    commented .env.example, README.md, and German HANDOVER.md all written
[ ] BUILD_PROGRESS.md shows all 20 items complete

═══════════════════════════════════════════════════════════════
PART G — RESUME PROTOCOL (for every future session)
═══════════════════════════════════════════════════════════════

On any new session, with no other instruction:
  1. Read BUILD_PROGRESS.md and BUILD_SPEC.md.
  2. Find the first unticked item.
  3. Resume it immediately. Write no preamble, ask no question, give no summary
     of what was done before.
  4. Apply Part A in full for the entire session.

PART A — NO-STOP PROTOCOL:
  1. Never end a turn with a question.
  2. Never end a turn by announcing what comes next.
  3. Never write a progress report or status update mid-build.
  4. Never stop at a phase boundary.
  5. On low context: update BUILD_PROGRESS.md, keep working.
  6. Ambiguity → pick per DESIGN_TOKENS.md + business model, log in DECISIONS.md.
  7. If something breaks, fix it.
  8. Batch aggressively.
  9. Keep prose output near zero.

═══════════════════════════════════════════════════════════════
PART H — DEPLOYMENT TARGET (added by operator mid-build)
═══════════════════════════════════════════════════════════════

Item 21, run only after items 4–20 are complete.

GIT
  remote origin  https://github.com/skillLeo/dkgz-platform.git
  Commits are authored by skillLeo <hassam.dev.571@gmail.com>.
  NO Co-Authored-By trailer, NO Claude/AI attribution, no other author.

SERVER (Hostinger shared hosting, CI/CD already wired from GitHub)
  ssh -p 65002 u290685119@46.202.183.38
  document root  domains/dkgz.skillleo.com/public_html/
  → the "public_html IS the document root" layout in DEPLOYMENT.md:
    Laravel core sits one level above, public/ contents go into public_html,
    and index.php paths are rewritten accordingly.

DATABASE (server)
  DB_DATABASE  u290685119_dkgz
  DB_USERNAME  u290685119_dkgz
  password supplied out of band — never committed. It belongs in the server
  .env only. `.env` stays git-ignored; `.env.example` ships commented and empty.

POST-DEPLOY VERIFICATION over SSH
  php artisan migrate --force
  php artisan db:seed --class=ProductionSeeder --force
  php artisan storage:link (SafeStorage fallback if symlinks are disabled)
  php artisan config:cache route:cache view:cache
  confirm the cron line is registered, then curl the homepage and /anmelden
  and check HTTP 200 plus the correct built asset hashes.
