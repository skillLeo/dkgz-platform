# BUILD_PROGRESS — Closeout

## B · Customer told when nothing happens ✓
- `anfrage-keine-rueckmeldung` template (German, names the reference, gives the
  office phone and address, never blames the customer, never says "try later").
- `unanswered` status, distinct from `expired`, added to the enum on both drivers.
- `NotifyCustomerNoResponseJob` — idempotent, refuses to fire on an accepted request.
- Fires from the expiry sweep and from the last decline; office mail raised alongside.
- Admin: "Kunde benachrichtigt" line with timestamp, plus "Kunde erneut benachrichtigen".
- Attention queue lists requests whose customer is still uninformed.
- 5 tests.

## C · Partner warned about lapsing cover ✓
- `haftpflicht-laeuft-ab` template; reminders at 30 / 14 / 3 / 0 days.
- `liability_reminders` table with a unique key, so no reminder ever repeats.
- `dkgz:check-liability-cover`, daily 07:00; logs the drop-out to the activity log.
- Portal banner (warning, then blocking), dashboard status dot with the date.
- **Switchable**: `business.require_valid_liability_cover`, default true — D-11.
- 7 tests.

## D · E-mail deliverability ✓
- `MailDomainCheck` resolves live SPF and DMARC, states present / missing /
  misconfigured in German, and suggests the exact record to add.
- Panel with copy buttons, a prominent no-SPF warning, and a re-check action.
- Provider presets: Brevo, Mailgun EU, Postmark, generic SMTP.
- Reply-To, bounce address, DKIM selector and unsubscribe address as settings.
- List-Unsubscribe on the monthly statement only, never on transactional mail.
- 5 tests.

## E · Bank details ✓
- `features.collect_bank_details`, default **false**; tab hidden and endpoint 404s.
- When on: German explanation of purpose, field explicitly optional.
- `dkgz:purge-bank-details` clears everything stored. D-12.
- 10 tests (incl. existing IBAN checksum coverage).

## F · Missing pieces ✓
1. `/widerruf` — already present, in the footer and editable.
2. Placeholder warning on legal pages; clears on first save.
3. sitemap.xml and robots.txt — already present.
4. Error pages — one styled German screen serving 404/419/500/503.
5. Commission invoice PDF — sequential `DKGZ-RE-YYYY-NNNN`, private, attached to
   the statement, gated by `business.generate_commission_invoices`. 5 tests.
6. Cookie consent — appears only when an analytics ID is configured.
7. `dkgz:backup-database`, daily 02:30, keeps 14, both drivers. 3 tests.
8. Wunschtermin **and** Dringlichkeit — both already captured.
9. Leistungsarten noted as provisional in HANDOVER.md.
10. Accept/decline rate-limited to 10/min per user.
11. Poll cadence is a setting; polling already paused on a hidden tab.

## G · Demo ✓
- `dkgz:seed-demo` — three partners (bundesweit / 40000–42999 / unavailable),
  six completed assignments with commissions across two months, two live requests
  at 40589 with deadlines today. Verified: both open requests match 2 partners.
- `dkgz:reset-demo` — removes exactly the marked data, verified back to zero.
- `dkgz:set-admin-password` prints once and forces a change at next sign-in.
- **New**: `/admin/profil` — staff had no way to change their own password at all.
- Hero graphic committed; homepage shows no dashed box.
- `DEMO_SCRIPT.md` in German, all credentials listed.

## H · Acceptance
339 tests passing · 59 pages rendering · vite clean · pint clean · deployed.

---

# Handover pass

## B · Customer told when nothing happens — completed
Already built in the closeout pass; the **zero-match** path was the gap and is now
closed. A request that matches nobody keeps status `new` with `matched_count = 0`,
so the job's guard was skipping it — the worst of the three cases stayed silent.
6 tests: expiry, last-decline, zero-match, still-open, accepted, and no-double-send.

## C · Liability warnings — verified, already complete
Template, 30/14/3/0-day reminders with unique-key send tracking, daily 07:00
command, portal banner, dashboard status dot. 7 tests.

## D · Bank details — verified, already complete
`features.collect_bank_details` default false, tab hidden and endpoint 404s,
purge command. 10 tests.

## E · Commission invoice PDF — verified, already complete
Sequential `DKGZ-RE-YYYY-NNNN`, dompdf, private disk, attached to the statement,
gated by `business.generate_commission_invoices`. 5 tests.

## F · Remaining gaps — all verified present
sitemap/robots, four error states on one German screen, cookie gate (confirmed
`analytics_configured: false` live, so no banner), accept/decline throttled,
`poll_seconds: 45` live with polling paused on hidden tabs, urgency selector
alongside the date, Reply-To/bounce/List-Unsubscribe, `/widerruf` live and in the
footer with the placeholder warning.

## G · Flow report generated from code — new
`dkgz:generate-flow-report` writes FLOW_REPORT.md by counting: screens off the
filesystem, routes off the router, tasks off the scheduler, templates and roles
off the database. Every heading count matches its own table, verified by 5 tests.

It refuses to print a test figure it did not verify. The first attempt ran the
suite nested and reported 231 of 343 — PHPUnit only applies phpunit.xml's `<env>`
entries when the variable is not already set, so the child inherited
`APP_ENV=local`, kept CSRF on, and 419'd every form test. The child now runs with
those variables cleared, and only a clean run is recorded.

## H · Production verification
1. Admin password rotated; forced change on first sign-in verified live.
2. Sending domain resolved: site runs at `dkgz.skillleo.com`, sends as
   `no-reply@dkgz.de`. The panel now names both and explains which DNS matters.
3. DEMO_SCRIPT.md executed against production: 40589 matched 2 partners,
   accepting closed it for the other, completion blocked before both uploads,
   850,00 € produced 127,50 € / 722,50 €.
4. **Mail does not leave the server.** `MAIL_HOST` is empty and `MAIL_SCHEME` was
   `tls`, which Laravel 13 rejects. Scheme corrected; the host needs the client's
   SMTP credentials. The queue itself drains correctly (11 → 4 → 0 by hand), so
   this is purely the missing transport, plus the absent cron.
5. Four legal pages live and all flagged as placeholders, hero graphic serving,
   all three demo logins present.

## Acceptance
348 tests · 59 page renders · vite clean · pint clean · deployed and verified.
