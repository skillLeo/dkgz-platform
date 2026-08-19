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
- [ ]  9. Public site: homepage first, then all remaining public pages
- [x] 10. Matching engine + first-accept-wins + concurrency test (16 tests, all green)
- [x] 11. Assessor portal — controllers, routes, layout (screens land with item 17)
- [x] 12. Assignment workspace, uploads, completion, commission — 28 commission tests, 9 privacy/document tests
- [x] 13. Admin panel — 18 screens incl. the matching trail; 30 per-role gate tests green
- [x] 14. Content management, branding, integrations, email template editor with live preview
- [ ] 15. All 18 emails, queued, logged
- [ ] 16. Review and rating flow
- [ ] 17. Mobile app shell across portal, admin and public
- [ ] 18. Full Pest suite green
- [ ] 19. Accessibility + responsive pass at 320/360/390/414/768/1024/1280/1440
- [ ] 20. Deployment artefacts and German HANDOVER.md
- [ ] 21. Push to origin (skillLeo authorship) + deploy to dkgz.skillleo.com, verify over SSH
