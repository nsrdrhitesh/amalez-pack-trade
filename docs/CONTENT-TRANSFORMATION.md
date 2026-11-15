# Confirmed-service content release

## Checklist
- [x] Inspect existing CMS, production schema and previous deployment (workflow 43632193554 succeeded; six migrations, one owner, MySQL connected).
- [x] Research supplied references and primary packaging sources.
- [x] Prepare only five main services, with researched format guides below them.
- [x] Generate, inspect and optimize original product images; record prompts, mappings and ALT text.
- [x] Write ten substantial original articles, two for each service.
- [x] Prepare repeatable, previewable content import with preservation checks; no schema migration unless needed.
- [x] Update brand identity, homepage, About, SEO and schema.
- [x] Validate admin editing, service paths, media, articles, metadata and contact/moderation behavior.
- [x] Build, tests, secret scan and release staging.
- [ ] Review production content changes and obtain approval before applying the content import.
- [ ] Deploy existing repository/workflow and verify live release.

Brand is Amalez PackTrade. Exactly five top-level confirmed services: Rotogravure Pouches, Digital Printed Pouches, Flexo Labels, Paper Boxes, Corrugated Boxes. Nested formats are researched discussion options; dimensions, materials, finishes, quantities and availability require project-specific confirmation. No facility, machinery, certification, location, guaranteed performance or lead-time claims.

Existing demo records will be preserved as drafts, never deleted. Existing editor changes will cause the importer to stop for review rather than silently replace them. Administrator accounts, inquiries, comments, private media and environment credentials are outside import scope. Indexing stays closed until publication approval. The optional reviewed indexing switch covers complete eligible pages only; provisional legal pages remain noindex.

## Validation and production baseline

The 200 original built-in-generated images are saved as optimized WebP files, with 200 unique SHA-256 checksums and 17,016,136 bytes total. The manifest includes per-page mappings, purpose, scene prompts, ALT text and dimensions. The full library was reviewed in contact sheets with selected full-size checks. Public previews were inspected at 375, 430, 768, 1024, 1440 and 1920 pixels; the mobile menu and three-level service breadcrumbs worked, and the inspected console showed no JavaScript errors.

The suite passes 67 tests with 1,097 assertions, including original authentication/CMS coverage and the new content, preservation, media, hierarchy, taxonomy, schema and indexing checks. Production Vite build succeeded. Route and Blade cache compilation and Composer validation passed. The defined-pattern candidate secret scan found no matches. No new dependencies or database migrations were introduced.

The existing GitHub deployment run 43632193554 succeeded for commit 4caca1f. Production still has six migrations, one administrator, ten initial pages, seven initial service concepts, three initial articles and seven initial media assets. The new content import has not run there.

Latest private database backup: cms-before-20261115-042456.sql.gz (server UTC filename), 11,456 compressed bytes, SHA-256 fa6942d5243d042a54577d81a2a36d53dfd9631a9f567ee66506ef50a49579b6. Gzip contents, schema/table counts, row counts and completion marker were verified. The export stays outside the public directory on Hostinger; no restore drill was performed.

## Private staging and publication review

The release boots on Hostinger with existing production settings and database. Platform checks, configuration, route and Blade caches, and the staged kernel homepage smoke test passed. The live release remains 4caca1f; the new content is not applied and main has not been pushed for this release.

The production content fingerprint is f42c3006f43beec5c81382222d5513d0f821f7332a9a67840be72e961deb6057. The plan creates 25 service pages, ten articles and 200 media records, updates six core pages, retains the seven former service records and three former articles as drafts, and makes Infrastructure a draft with its menu entry disabled. Provisional legal text is retained with corrected branding and noindex metadata. There are no pending schema migrations and no account changes.

The importer verifies the review fingerprint before applying and again inside its transaction after locking the reviewed content rows, preventing edits during import from being silently replaced. Visitor submissions remain outside that lock scope. A rerun of the same release preserves subsequent editor changes.

Full validation: 67 tests / 1,097 assertions; targeted import tests also rerun after the transaction guard. Local full-library preview: http://127.0.0.1:8003/ using an isolated SQLite file and a distinct preview session cookie. It contains the actual 200-image gallery mappings. Its development-only account is not a production account.

Pending approval: apply this content plan and publish through the existing workflow. An explicit indexing option can enable the 41 complete eligible URLs; legal pages remain noindex and archived records stay out of the sitemap. Production environment credentials, APP_KEY, users, inquiries, comments and existing private files remain untouched.
