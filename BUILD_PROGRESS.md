# DKGZ Build Progress

Resume rule: read BUILD_SPEC.md, continue at the first item not marked `[x]`.

- [x]  1. Design import — all 11 paths in `design-src/`
- [x]  2. Token extraction — `DESIGN_TOKENS.md`, `resources/css/app.css`
- [x]  3. Vite/Inertia/Ziggy config + all 27 migrations (`migrate:fresh` green)
- [x]  4. Models, MoneyCast, relationships, factories — 56 tests green
- [x]  5. Roles (6), permissions (44), 13 policies, middleware, production + demo seeders — 73 tests green
- [x]  6. Settings, content_blocks, Branding, SafeStorage, DynamicConfigServiceProvider — 88 tests green
- [x]  7. Base component library — 14 Base, 7 Feedback, 8 Data, 6 Layout, 8 composables
- [x]  8. Auth shell + 11 auth screens, 4-step registration, 2FA, invitations — 32 auth tests
- [x]  9. Public site — 12 pages + 4 error states, 22 tests green
- [x] 10. Matching engine + first-accept-wins + concurrency test (16 tests, all green)
- [x] 11. Assessor portal — all 12 screens, 19 portal tests green
- [x] 12. Assignment workspace, uploads, completion, commission — 28 commission tests, 9 privacy/document tests
- [x] 13. Admin panel — 18 screens incl. the matching trail; 30 per-role gate tests green
- [x] 14. Content management, branding, integrations, email template editor with live preview
- [x] 15. 19 mail templates, queued, DB-driven with Blade fallback, every send logged
- [x] 16. Review and rating flow incl. the low-rating feedback step and one-time redirect
- [x] 17. Mobile app shell — bottom tab bar, top app bar, Mehr sheet, filter sheet, manifest + icons
- [x] 18. Full Pest suite green — 268 tests, order-independent
- [x] 19. Token discipline verified: zero arbitrary values, zero inline hex, zero CDN font requests
- [x] 20. DEPLOYMENT.md (both layouts), 2 × .htaccess, deploy.sh, cron line, .env.example, README.md, German HANDOVER.md
- [x] 21. Pushed to origin and deployed to https://dkgz.skillleo.com — every
      public route 200, admin login verified end to end, production log clean.
      **One step left for the operator:** the minute cron. `crontab` is not
      available to this account, so it must be added in hPanel — see below.

---

## Outstanding — operator action required

**Add the cron job in hPanel → Advanced → Cron Jobs, interval "Every minute":**

```
/home/u290685119/domains/dkgz.skillleo.com/public_html/queue-tick.sh
```

Until this exists, no e-mail leaves the system: every message queues in the
`jobs` table and the admin System page will show the queue depth climbing.
`crontab` is not exposed to this hosting account over SSH, so this is the one
step that cannot be scripted from here.

The script is already installed and tested on the server; it runs the scheduler
and then drains the queue, guarded so a slow job cannot stack workers.
