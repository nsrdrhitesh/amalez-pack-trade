# Production-readiness inspection — 2026-11-13

> This document records the original pre-deployment inspection. For current deployment status, see [HOSTINGER-DISCOVERY.md](HOSTINGER-DISCOVERY.md). The application is live on HTTPS and GitHub Actions deployment has completed successfully.

## Verified before deployment changes

- Local project: `laravel-fullstack-starter`; Laravel 12.71.0; PHP requirement `^8.2`.
- Local production Composer platform requirements pass on PHP 8.2.4. This is not a check of Hostinger's PHP runtime.
- Existing Git branch `main` tracks `https://github.com/nsrdrhitesh/amalez-pack-trade.git`. No second repository is needed.
- Blade, Tailwind 4, Alpine 3, and Vite 6 are already configured. Vite has separate public/admin CSS entries and a shared JS entry. Assets must be built before packaging; `node_modules` must not be deployed.
- Homepage and sitemap are GET-only and stateless, using configuration and Blade with no database queries.
- Retained admin login, users, and activity logs use MySQL/Eloquent. The public initial deployment does not justify database creation or migrations.
- Local `.env` has local/debug settings and database-backed sessions/cache/queues. It must not be copied to the server. Its key and other secret values have not been included in this report.
- Public root is `public/`. `public/index.php` assumes the application is its parent; this must be adapted only if the verified hosting arrangement separates `public_html` from the application root.
- `storage/` and `bootstrap/cache/` need writable directories under the hosting account. Private data, vendor, and `.env` must remain outside the web root. The public site does not need a public storage symlink.
- `public/hot` is absent. `.gitignore` excludes local environment variants, keys, data exports, runtime files, dependencies, and tooling; a repository preflight scanner is available.
- Domain A lookup returned `2.57.91.91`. A verified HTTPS request returned 200 with title **Parked Domain name on Hostinger DNS system**, without project homepage text. This is not a Laravel deployment success.

## Production decisions prepared locally

- Use the newly specified display name **Amlez Pack Trade** through configuration.
- Keep production secrets exclusively on the server. An unpopulated production template contains no application key or database credentials.
- Use `SESSION_DRIVER=file`, `CACHE_STORE=file`, and `QUEUE_CONNECTION=sync` for the initial public website.
- Keep `ADMIN_REGISTRATION_ENABLED=false`; local-only middleware already rejects registration in production.
- Provide an explicit `ADMIN_ENABLED=false` production option so the retained database-dependent admin is unavailable until a separately approved database setup. Local admin behavior remains enabled by default.
- Keep indexing disabled while company information and legal details remain unapproved.

## Hosting facts not yet verified

Hosting plan and quota; exact website association; document root and account path; SSH availability; server PHP version/extensions; CLI Composer/PHP; SSL management state and HTTPS redirect; filesystem permissions; Git integrations and auto-deploy settings; existing files/secrets/backups. A parked public page alone cannot establish these facts.

The official hosted Hostinger MCP connection was missing. It has now been added, and browser authorization succeeded. The current session still exposes only the older WordPress-site tools, not web-hosting account operations. Restart the app or start a new Codex session to load the newly configured hosted server. Existing older Hostinger servers were preserved; none were removed.

## Deployment architecture selection remains pending account inspection

## Completed local validation

After the readiness changes: **29 tests passed, 311 assertions**. Vite production build succeeded, Blade templates compiled, route-cache compilation succeeded (then cleared for local editing), and Composer validation passed. Tests verify the admin-disable switch, configurable official display name, and public rendering without any database configuration. The local secret scan reported no findings. These checks do not verify the hosting runtime or a live Laravel deployment.

Laravel deployment requirements were cross-checked against https://laravel.com/docs/12.x/deployment . In particular, only `public/` may be served and `storage/` plus `bootstrap/cache/` must be writable.

## Candidate architecture, not yet configured

Preferred candidate: one GitHub Actions pipeline builds/tests an artifact and deploys over verified SSH into release directories outside the web root, preserving a shared server `.env` and writable storage. The public document root points to the selected release's `public/`, if supported. No workflow, SSH key, secret, remote path, or rollback capability has been claimed as configured.

If SSH cannot be used, explain the exact limitation before selecting Hostinger's supported Git or archive method. Do not install two automatic deployment mechanisms. Do not upload a Laravel source root directly into `public_html`.

Before switching any existing content or editing DNS, inspect and back up the actual target, present the exact changes, and obtain the requested approval. No DNS, hosting-account setting, production-file, database, or live-site changes have been made.
