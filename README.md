# Amalez PackTrade

A Laravel 12 packaging and printing website with a Blade, Tailwind, Alpine and Vite frontend and a MySQL-backed CMS.

The confirmed main services are Rotogravure Pouches, Digital Printed Pouches, Flexo Labels, Paper Boxes and Corrugated Boxes. Twenty researched format guides sit below them. Ten original articles provide practical buyer guidance. The original 200-image WebP library is illustrative, not evidence of delivered work, factory ownership or tested performance.

## Application structure

- `app/Http/Controllers/Admin`: existing authentication and CMS modules.
- `app/Http/Controllers/Frontend`: public content, protected media delivery, inquiries and moderated comments.
- `app/Models`: normalized content, taxonomy, media, SEO, navigation and private visitor records.
- `app/Services/BusinessContentRelease.php`: reviewed, fingerprint-protected editorial import.
- `database/content`: service/article/page source and asset manifest. After import, public content comes from editable database records.
- `resources/views/site`: reusable public templates; no hardcoded service catalogue.
- `resources/views/admin/cms`: editors, media, inquiries, moderation, settings and roles.
- `public/images/business`: 200 optimized original product and editorial images.
- `public/images/branding`: original SVG mark and light/dark wordmark concepts.
- `docs/research`: source record and image art direction.

## Development

Install locked dependencies with `composer install` and `npm ci`. Copy `.env.example` to `.env` only when no local environment exists, then configure the intended local database. Never replace an existing application key or database credentials.

For a new, confirmed local database, run migrations, then `php artisan db:seed --class=CmsSeeder`. Set `CMS_ENABLED=true` and `ADMIN_ENABLED=true` in the local environment. Keep public registration closed and use the existing `php artisan admin:create` command for an administrator.

`php artisan content:transform` prints the editorial plan and current fingerprint. After reviewing that local target, apply it using `php artisan content:transform --apply --expected=THE_REVIEWED_HASH`. The importer preserves previous services/posts as drafts and stops if content changed since review. It is versioned and does not overwrite later editor changes when rerun. Production application requires explicit approval of its own reviewed plan.

Build with `npm run build`; start with `php artisan serve`. Public routes include `/`, `/about`, `/services`, nested `/services/...`, `/blog`, `/blog/{slug}`, `/contact` and `/faq`. Admin entry is `/admin/login`; the protected dashboard is `/admin/dashboard`. Production bootstrap registration remains closed.

## Validation

Run `php scripts/verify-content-assets.php`, `php artisan test` and `npm run build`. PHPUnit forces its testing environment and in-memory SQLite database, independent of a preview server's environment. The asset check validates local WebP files, dimensions, checksums, uniqueness, ALT text and size limits.

The CMS supports owner/manager/editor authorization, safe raster uploads, HTML purification, CSRF/session protection, moderation, private inquiries, SEO fields and validated manual JSON-LD. Optional analytics requires visitor consent; inquiry email notification requires separately configured SMTP.

## Production

Use the existing `nsrdrhitesh/amalez-pack-trade` repository, `main` branch and GitHub Actions/verified SSH release workflow. The Laravel application, shared environment, private media and backups remain outside the web root. Never commit `.env`, SSH keys, database exports, local tooling or runtime files.

The reviewed content release needs no schema migration. Stage and back up before applying its production content plan; preserve existing users, credentials, APP_KEY, inquiries, comments and private storage. The optional `--enable-indexing` switch is for an approved publication: eligible complete pages enter the sitemap, while provisional legal pages remain noindex and Infrastructure remains a draft.

See `docs/CONTENT-TRANSFORMATION.md` for current validation and release status. Historical readiness notes describe earlier deployment stages and do not supersede the current release record.
