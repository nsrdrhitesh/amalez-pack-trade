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
- [x] Receive explicit publication and indexing approval; apply the reviewed import.
- [x] Deploy and verify the live release.

Brand is Amalez PackTrade. Exactly five top-level confirmed services: Rotogravure Pouches, Digital Printed Pouches, Flexo Labels, Paper Boxes, Corrugated Boxes. Nested formats are researched discussion options; dimensions, materials, finishes, quantities and availability require project-specific confirmation. No facility, machinery, certification, location, guaranteed performance or lead-time claims.

Existing demo records were preserved as drafts, never deleted. Existing editor changes will cause the importer to stop for review rather than silently replace them. Administrator accounts, inquiries, comments, private media and environment credentials are outside import scope. Publication and indexing were explicitly approved. The optional reviewed indexing switch covers complete eligible pages only; provisional legal pages remain noindex.

## Validation and production baseline

The 200 original built-in-generated images are saved as optimized WebP files, with 200 unique SHA-256 checksums and 17,016,136 bytes total. The manifest includes per-page mappings, purpose, scene prompts, ALT text and dimensions. The full library was reviewed in contact sheets with selected full-size checks. Public previews were inspected at 375, 430, 768, 1024, 1440 and 1920 pixels; the mobile menu and three-level service breadcrumbs worked, and the inspected console showed no JavaScript errors.

The suite passes 67 tests with 1,119 assertions, including original authentication/CMS coverage and the new content, preservation, media, hierarchy, taxonomy, schema and indexing checks. Production Vite build succeeded. Route and Blade cache compilation and Composer validation passed. The defined-pattern candidate secret scan found no matches. No new dependencies or database migrations were introduced.

## Completed publication — 22 November 2026

The approved import published five main services and 20 nested guides, ten original articles, 200 original media assets and six updated core pages. Seven previous services and three previous articles remain as drafts. Infrastructure is a draft with its navigation entry disabled. Existing accounts, visitor records, private images, credentials and application key were preserved. No migrations were added. A private pre-publication backup was verified and retained outside the website; no restore drill was performed.

The initial workflow passed validation but timed out during its SSH connection. The follow-up deployment succeeded: [workflow 44995005958](https://github.com/nsrdrhitesh/amalez-pack-trade/actions/runs/44995005958), runtime commit 301656b6424ecf06d27267deccabb597b5629b38. Inline article images now retain explicit dimensions during import and editor saves. All 67 tests pass with 1,119 assertions; asset verification and the Vite build also passed.

Live HTTPS checks verified 41 eligible sitemap URLs, 201 rendered image URLs, 119 JSON-LD blocks, canonical URLs, indexing metadata, image dimensions and ALT text. Three provisional legal pages remain noindex. Archived content and Infrastructure return 404. Admin login is available; guests are redirected from the dashboard. Public registration remains closed. CSRF rejection, invalid-login rejection and protected environment-file access were verified.

Synthetic enquiry and comment submissions were verified in storage. The server ignored a client-supplied approved comment status, stored it as pending and kept it nonpublic. Only these labelled test records were closed/rejected and retained. No notification email was sent. Enquiries remain accessible through the protected admin panel; SMTP notifications require future configuration.

Responsive preview checks covered 375, 430, 768, 1024, 1440 and 1920 pixels. No dependencies were added. Final company contact details, legal content and business claims require client confirmation. Index eligibility does not guarantee Google indexing or ranking.