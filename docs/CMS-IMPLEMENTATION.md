# Dynamic CMS implementation

Extend the existing application and production database. Preserve the administrator, application key, authentication, and industrial visual direction. Official name: Amalez Pack Trade; short name: PackTrade. No manufacturing claims.

1. Audit existing source and production (completed: Laravel 12, one admin, five migrations, shared private environment, verified SSH/GitHub workflow).
2. Add normalized page, service hierarchy, blog/taxonomy, media, section, SEO, menu, inquiry, comment, settings, and audit models. Add owner/manager/editor permissions without changing public registration.
3. Build shared CRUD/editor components, hierarchy validation, safe image uploads, rich text sanitization, publication scheduling, and reusable SEO/schema rendering.
4. Replace public hash navigation with independent dynamic routes while retaining the existing palette and packaging imagery. Seed editable illustrative content and three original articles idempotently; never seed accounts.
5. Test all modules, security, relationships, optional states, hierarchy resolution, SEO, moderation, and uploads. Build assets and scan secrets.
6. Back up and verify production, review additive migrations, request database-change approval, stage the new release, apply only approved changes, deploy through the existing workflow, and verify live behavior. Do not push an automatically activating release before its production schema is ready.

Rich text uses HTML Purifier on the server and Trix in the editor. Only validated raster images are accepted. Media files live in shared private storage and are delivered through a controlled image route. Trusted owner-only script settings accept approved provider configuration, not arbitrary editor HTML.
