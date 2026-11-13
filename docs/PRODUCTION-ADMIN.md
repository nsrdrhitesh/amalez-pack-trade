# Production administrator bootstrap

> Production activation is complete. Login is available at https://amalezpacktrade.com/admin/login. The first administrator is provisioned; bootstrap and unrestricted registration are disabled. `/admin/register` now intentionally returns 404.

The existing session guard, users model, admin role, auth views, and dashboard are retained. Public registration remains disabled: `ADMIN_REGISTRATION_ENABLED=false` in production, even while bootstrap is available. Local registration behavior is unchanged.

## Activation prerequisites

1. Inspect the actual MySQL target and its migration history. Obtain approval for the exact pending migrations before running them. Never use `migrate:fresh`, reset, or demo account seeders on production.
2. Preserve the existing server `.env` and `APP_KEY`. Configure only the verified database connection and set `ADMIN_ENABLED=true` after the schema is ready. Secure file sessions/cache and the current queue configuration can remain unchanged.
3. Temporarily set `ADMIN_BOOTSTRAP_ENABLED=true`, rebuild Laravel configuration cache, and issue `php artisan admin:invite-bootstrap <approved-email> --minutes=30` over trusted SSH. Lifetime must be 1–60 minutes. This refuses an existing administrator, a consumed bootstrap, or an already registered email.
4. The command writes a 256-bit random token only to `storage/app/private/admin-bootstrap-token`, permissions 0600, outside the public root. Only a SHA-256 digest, bound email, expiration, and consumption timestamp are stored in MySQL. The command never prints the token. Reissuing an unconsumed invitation replaces the previous digest.
5. Use `/admin/register` over HTTPS. Supply the secret exclusively in the masked POST form field alongside valid CSRF and the approved email. Never place it in a query string, command argument, browser output, logs, issues, or GitHub. Validation redirects exclude it from flashed input. Do not introduce request-body logging on this route.
6. A transaction locks the permanent singleton invitation row, checks expiration and the absence of an existing admin, validates the form, hashes the password through the existing model cast, creates the administrator, and consumes the invitation atomically. Role fields from the browser are ignored. Validation failures do not consume the invitation. Registration then returns 404 for guests. The consumed record prevents reissuing bootstrap even if an account is later removed.
7. After provisioning, set `ADMIN_BOOTSTRAP_ENABLED=false`, rebuild configuration cache, and retire the plaintext private token file. Keep the consumed database record. Verify login, dashboard, POST logout, unauthorized access, HTTPS cookies, and production logs. Use a secure credential handoff; never put the administrator's password in repository files or chat.

GET registration is limited to 30 requests/minute and POST to 5/minute per client; existing login IP/email throttles and CSRF remain active. Registration is not linked publicly in production. `/admin/register` intentionally closes after first provisioning; login and the authenticated dashboard remain available.

## Schema

The original migrations create users, reset-token/session tables, optional cache/jobs tables, the `is_admin` boolean, and activity logs. The new additive migration creates `admin_bootstrap_invitations` with a singleton primary key, email, token digest, expiration, consumption timestamp, and timestamps. It does not alter or delete existing users. Initial production had no database configuration; live activation must wait for database selection and migration approval.

The deployment smoke check permits an enabled admin module only with MySQL and the required user, activity, and invitation schema. It never runs migrations automatically, never changes existing database credentials, and never rotates the production application key.

## Validation

Implementation validation: 38 tests, 383 assertions passed locally. Tests cover valid and invalid invitations, email binding, expiration, one-time consumption, validation rollback, duplicate email, privilege control, HTTPS, rate limiting, CSRF, secret redaction from old input, and existing admin authentication. A regression test also verifies that repeated registration-page views do not consume the submission limit. Login, registration-page views, and registration submissions use separate throttle prefixes.

## Verified production database activation — 2026-11-14

The user authorized a new, dedicated Hostinger MySQL database and user, leaving existing databases untouched. Its credentials and empty schema were verified over pinned-key SSH before configuration. All five reviewed migrations were applied only to that new target, with no data imports or seeders. Production now uses MySQL, has five migration records and eleven tables, and has `ADMIN_ENABLED=true`.

The database password was entered through a masked prompt and transferred in memory over SSH; no local password file was populated. The protected server environment has mode 0600. An independent comparison against the pre-configuration backup confirms the existing `APP_KEY` was preserved. File sessions remain encrypted, with secure/HTTP-only cookies and SameSite Lax. Unrestricted registration remains disabled.

Initial live provisioning detected that numeric Laravel throttle middleware shared a counter between form views and form submissions. The valid bootstrap request was throttled before creating an account. Route-specific prefixes corrected the issue; [workflow 43483779228](https://github.com/nsrdrhitesh/amalez-pack-trade/actions/runs/43483779228) passed and deployed the fix. The resulting diagnostic error was retained in the production log for traceability.

## Completed first-administrator verification

The administrator entered their identity and chosen password through a masked, in-memory setup prompt. No local password copy was saved. A temporary email-bound invitation was issued server-side, and actual HTTPS requests verified the existing registration, login, dashboard, and logout routes. Checks passed for missing CSRF rejection, invalid invitation, invalid registration data without consumption, successful registration, password hashing, valid and invalid login, authenticated dashboard access, auth-form redirects, POST-only logout, post-logout protection, and closure against invitation reuse or duplicate registration. The account was logged out after verification.

Independent server inspection confirms exactly **one user / one administrator**, five recorded migrations, `ADMIN_ENABLED=true`, `ADMIN_REGISTRATION_ENABLED=false`, and `ADMIN_BOOTSTRAP_ENABLED=false`. The invitation is consumed, its digest cleared, and the plaintext token retired. The original application key and secure encrypted file-session settings remain intact. MySQL connectivity is verified. The production HTTP kernel also returned **403** for an in-memory non-admin principal, with the user-record count unchanged; this probe did not create a non-admin test account.

Final anonymous HTTPS checks: homepage **200**, `/admin/login` **200**, `/admin/dashboard` **302** to login, `/admin/register` **404**, GET `/admin/logout` **405**, and `/.env` **403**. Authenticated dashboard and POST logout were separately verified in the live setup flow. Production logs retain the single diagnosed setup-probe error; no additional errors appeared during successful verification. No existing database or prior records were modified, no seeders or old-data imports were run, and the application source remains deployed through the approved GitHub workflow.

The session flow follows [Laravel authentication guidance](https://laravel.com/docs/12.x/authentication), with atomic invitation consumption using [database transactions](https://laravel.com/docs/12.x/database#database-transactions).
