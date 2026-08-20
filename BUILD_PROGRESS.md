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
