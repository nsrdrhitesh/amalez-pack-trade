# Live hosting discovery — 2026-11-13

Verified via the signed-in Hostinger hPanel UI (the hosted MCP connection is configured but its operations are not exposed in this session):

- Current application commit: `3182457cb7876daa796f7f972d2d91ef36f49e9e`, branch `main`; clean working tree before this report.
- Active plan: **Business Web Hosting**, expires **2027-06-17**.
- Account resource page: **200 GB** storage, **2.07 GB** used, **197.93 GB** free; **44,099 / 600,000** inodes; **3,072 MB** memory limit.
- The plan's website inventory contains nine entries, including subdomains. None is `amalezpacktrade.com`.
- The home page lists `amalezpacktrade.com` as a **Domain** and offers **Create a website**, rather than Manage site.
- Domain registration is active until **2028-11-07**. Domain-level SSL is shown active; this does not prove an application vhost has been created.
- Nameservers: `aurora.dns-parking.com`, `nebula.dns-parking.com`.
- Full DNS zone shown in hPanel has two records: `A @ → 2.57.91.91`, TTL **50**; `CNAME www → amalezpacktrade.com`, TTL **300**. No MX, TXT, DKIM, DMARC, or verification records were listed.
- Observed diagnosis: the domain is registered but no hosting website has been provisioned for it. Public requests therefore reach the parked-domain service rather than the Laravel application.
- An existing, unrelated website on the plan demonstrates that GitHub auto-deployment and Node 22 are available for that website. This is not verification of a PHP/Laravel deployment configuration for the new target, and its settings have not been changed.

The user approved creation of the PHP/HTML website, a dedicated deployment SSH key, encrypted GitHub Actions secrets, and a direct-origin DNS connection. The new website has now been provisioned. Hostinger created only `public_html/default.php` (42,559 bytes) and a domain-directory marker. No existing business website or database was present at the new target.

Verified runtime: PHP CLI **8.2.35**, web PHP **8.3.36**, Composer **2.9.8**, LiteSpeed, and all required PHP extensions. SSH is active on port **65002**. Its ED25519 host key was verified against hPanel. The Windows keyscan utility needed a compatible secure key-exchange setting; authenticated SSH access then succeeded. No VPS instance was listed; the VPS page offered setup products only.

Selected architecture: GitHub Actions builds/tests a self-contained artifact; verified SSH transfers it to private release directories; Laravel serves only the release's `public/` through the domain document root. Server connection values and private keys belong in repository Actions secrets, not this public report. The production package installer completed with **77 non-development dependencies** in isolated local staging. The local development `.env` and development vendor tree were preserved.

Provisioning automatically changed DNS to `ALIAS @ → amalezpacktrade.com.cdn.hstgr.net`, `CNAME www → www.amalezpacktrade.com.cdn.hstgr.net`, and added an FTP record. These automatic results differed from the user-approved direct-origin plan. The root and www records require alignment with the approved plan; nameservers remain unchanged. No database has been created or migrated.

Deployment script and workflow preparation are local until activation approval. Public placeholder replacement, backup verification, production bootstrap, final DNS inspection, and independent live checks remain pending.

## Subsequent staging validation

- Artifact for application commit `2574ddd7d87f1bccb998c9672102978ba1a2cf94`: 5,232,008 bytes; SHA-256 `bfc98b04bdb2b58c1a41ae82476cb338d0b9e75fb724f584d6fa7627e22cbfa7`. Uploaded to private application storage and its checksum verified on the server.
- Production Composer requirements passed on Hostinger. Configuration, route, and Blade caches compiled. The staged Laravel application returned HTTP 200 in an internal kernel request with the expected homepage text. Production mode, debug disabled, admin disabled, secure file sessions, file cache, sync queue, application URL, and nonempty application key were checked without printing secrets. No database was created or migrated.
- The smoke-test helper initially omitted the Composer autoloader. This was diagnosed and corrected in local commit `654eff1`; the corrected check passed on the staged release. The public document root has not been switched.
- A private backup of the sole generated `default.php` was created and its archive listing verified. Backup SHA-256: `fc93cb5d631588a7822d53db828781df21f658bdb980cb2cdf85ad807affbf2b`. Restoration has not been exercised.
- Six encrypted GitHub Actions secrets are configured, including the pinned SSH host key and private application deployment path. The deployment workflow is committed locally but has not been pushed or run.
- hPanel now shows `CNAME www -> amalezpacktrade.com`, TTL 300, saved and verified. The root remains the automatically provisioned CDN ALIAS. Changing its type in the editor did not persist; the unsaved editor was cancelled. Direct-origin DNS alignment remains incomplete.
- The current zone also contains Hostinger mail records (MX, SPF, DKIM, DMARC, autoconfiguration) that were absent during initial discovery. They were preserved; their creation source was not established.
- Approval was requested for the concrete final switch: preserve the generated placeholder backup, point `public_html` at Laravel's `public/`, and enable the prepared workflow. Public HTTP, asset, redirect, private-path, and browser checks must follow activation; they have not yet passed or been claimed.

## Live activation and automated deployment

The user subsequently approved Laravel activation and the deployment workflow. The placeholder directory and archive were retained privately. `domains/amalezpacktrade.com/public_html` now links to `applications/amalez-pack-trade/current/public`, with versioned application releases outside the document root and shared production environment/storage outside public access.

- The initial release activated successfully. The workflow was pushed to `main`; its first validation run exposed an ordering error: feature tests rendered Blade before Vite created its manifest. Building assets before tests fixed the pipeline.
- [Successful workflow run 43432943605](https://github.com/nsrdrhitesh/amalez-pack-trade/actions/runs/43432943605) built assets, passed **29 tests / 311 assertions**, packaged production dependencies, transferred over pinned-key SSH, activated release `1aa726a07b7dfd4d3c2346c4ed2eccc3d8c6659b`, and independently verified the public domain. Subsequent pushes run the same pipeline. The current release is available at `/release.json`.
- Public HTTPS homepage: **200**, expected Laravel content. All ten referenced local CSS, JavaScript, SVG, and WebP asset URLs returned **200**. Sitemap and robots.txt returned **200**. HTTP and HTTPS www returned **301** to the canonical HTTPS root. TLS validation succeeded without bypasses.
- `/.env`, `/.git/config`, and `/storage/logs/laravel.log` returned **403**; `/vendor/autoload.php`, `/admin/login`, and `/admin/register` returned **404**. Debug and admin remain disabled; the public site uses no database.
- Native Chrome visual inspection covered homepage widths **375, 430, 768, 1024, 1440**. Mobile navigation opened and closed. The observed console had no error entries. These are visual homepage checks, not automated whole-page overflow measurements. Mobile inspection identified missing whitespace around a hidden hero line break, corrected in the final handoff change.
- DNS currently serves the working site through Hostinger's managed CDN ALIAS, rather than the requested direct-origin A record. `www` points to the root. HTTPS, canonical redirects, and deployment verification work through the CDN; direct-origin DNS alignment was not completed. Nameservers and mail records were preserved during manual changes.
- Application key and environment are retained in private shared storage; SSH key and connection values are encrypted Actions secrets. No credentials were committed. Backup extraction listing and checksum passed; a restore drill has not been run. Previous application releases are retained for rollback.
- Company details and enquiry backend remain unfinished by design; indexing stays disabled pending content approval. No production database or migrations were introduced.
