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
