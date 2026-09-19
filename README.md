# Sari-Sari Store POS

Release **1.0.0** is a procedural PHP/MySQLi point-of-sale application for sari-sari stores and small groceries. Development phases 0–18 cover authentication and RBAC, master data, products and CSV import, inventory, online and offline POS, receipts, suppliers, reports, dashboard, backup/restore, security hardening, responsive UI, and production-readiness controls.

## Technology and Tested Environment

- PHP 8.x, procedural PHP, MySQLi only
- MySQL/MariaDB with InnoDB and utf8mb4
- HTML5, CSS3, JavaScript, Bootstrap 5 CDN, Fetch API
- Locally tested with PHP 8.2.4, MariaDB 10.4.28, Apache 2.4.56, and Chromium
- No PDO, framework, ORM, npm, Node.js build, React, or Vue

The application requires PHP extensions `mysqli`, `session`, `fileinfo`, `json`, and `mbstring`. It uses `random_bytes`, `password_hash`, `password_verify`, and `getimagesize`. GD is not required because images are validated rather than transformed. Backup/restore additionally requires `proc_open`, `mysqldump`, and the `mysql` client. OpenSSL is recommended for the server's HTTPS configuration. Offline POS requires browser support for service workers, IndexedDB, Fetch, and Web Crypto.

## Local XAMPP Setup

1. Place the project at `C:\xampp\htdocs\pos`.
2. Start Apache and MySQL in XAMPP.
3. Import `database/database.sql`. It creates and selects `pos_db` and loads the full current schema and foundation seed data.
4. Ensure `assets/uploads/products`, `storage/backups`, and `storage/logs` are writable by Apache.
5. Open `http://localhost/pos/`.

Local defaults are `localhost:3306`, database `pos_db`, user `root`, empty password, and base path `/pos`. These defaults are for local XAMPP only.

## Database Installation and Migrations

For a fresh installation, import `database/database.sql` once. Do not run the historical migrations after a fresh full-schema import.

For an existing deployment, create and verify a backup first, then apply only migrations that have not previously been applied, in filename order. The available migrations are `004_categories_brands_units.sql`, `005_products.sql`, `006_inventory.sql`, `007_basic_pos.sql`, `009_suppliers.sql`, `010_advanced_products.sql`, `013_backup_restore.sql`, and `014_offline_pos.sql`. Several phases required no schema migration, which explains the numbering gaps. ALTER-based migrations are apply-once scripts.

The live schema verified during Phase 18 contains 17 InnoDB tables, 22 permissions, 16 foreign keys, and utf8mb4 table collations. Do not use `DROP DATABASE` during normal deployment or upgrade.

## Production Configuration

Production settings are read from server environment variables. The project does not parse a `.env` file. Configure values in the web-server virtual host, service manager, or another protected host-level mechanism. Keep database passwords and other secrets out of the project directory, `.htaccess`, Git, deployment logs, and documentation.

| Variable | Local default | Production action |
|---|---|---|
| `POS_APP_ENV` | `local` | Set to `production` |
| `POS_APP_DEBUG` | `false` | Keep false; it is forced off in production |
| `POS_TIMEZONE` | `Asia/Manila` | Set the store timezone if different |
| `POS_BASE_URL` | `/pos` | Set the URL path, or `/` for domain root |
| `POS_DB_HOST` | `localhost` | Set the database host |
| `POS_DB_PORT` | `3306` | Set the database port |
| `POS_DB_NAME` | `pos_db` | Set the deployed database name |
| `POS_DB_USER` | `root` | Use a dedicated application user |
| `POS_DB_PASSWORD` | empty | Set a strong password |
| `POS_PRODUCT_UPLOAD_DIR` | project upload directory | Set an absolute writable filesystem path |
| `POS_PRODUCT_UPLOAD_URL` | base-path upload URL | Set the matching public URL path |
| `POS_BACKUP_DIR` | `storage/backups` | Prefer a restricted absolute path outside the document root |
| `POS_LOG_PATH` | `storage/logs/php-error.log` | Set a protected server log path; empty uses PHP's configured log |
| `POS_MYSQLDUMP_PATH` | auto-detected | Set the absolute utility path if not detected |
| `POS_MYSQL_PATH` | auto-detected | Set the absolute restore-client path if not detected |
| `POS_TRUST_PROXY_HTTPS` | `false` | Enable only behind a trusted proxy that overwrites `X-Forwarded-Proto` |
| `POS_ENABLE_HSTS` | `false` | Enable only after HTTPS works correctly for the whole production host |

Invalid environment modes, ports, base paths, upload URLs, or runtime storage paths fail closed. Database credentials remain centralized in the environment-aware configuration and are not duplicated elsewhere.

## Production Deployment

1. Deploy a reviewed release to its final directory and keep a rollback copy of the prior release.
2. Configure a dedicated database and a dedicated database user with only the privileges needed by the application. Do not run the application as MySQL `root`.
3. Import the full schema for a fresh install, or apply only outstanding migrations for an existing install.
4. Configure all required environment variables before serving traffic.
5. Point Apache at the project directory and enable `AllowOverride` for this directory so the included `.htaccess` protections apply. Apache must have `mod_rewrite` and authorization support (`mod_authz_core` on Apache 2.4). Keep directory indexes disabled.
6. Configure a valid TLS certificate and redirect public HTTP traffic to HTTPS at the server or trusted reverse proxy. Verify HTTPS before enabling HSTS.
7. Make application source and configuration read-only to the web-server account. Grant write access only to product uploads, backup storage, and the configured log directory.
8. Confirm PHP settings and resource limits, complete the initial Administrator procedure, and run the verification checklist below.

If the application is installed at a subpath, `POS_BASE_URL` must match it exactly, such as `/store-pos`. For a domain root, use `/`. Product upload URLs, redirects, the manifest, and service-worker scope then follow the configured path. The service worker derives its base path from its registration scope.

## PHP and Web-Server Settings

Recommended production PHP settings include `display_errors=Off`, `log_errors=On`, `session.use_strict_mode=1`, `session.use_only_cookies=1`, and `session.use_trans_sid=0`. Set `upload_max_filesize` and `post_max_size` at or above the application's 5 MB product-image limit and 2 MB CSV limit. Application validation still enforces those limits.

Under HTTPS, session cookies automatically use `Secure`, `HttpOnly`, and `SameSite=Lax`. Login regenerates the session ID, logout invalidates it, and authentication data is kept server-side. Local HTTP intentionally does not set `Secure`. HSTS is emitted only in production when HTTPS is detected and `POS_ENABLE_HSTS=true`.

The application sends no-store cache headers for PHP responses, removes `X-Powered-By`, and sends `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, and `Permissions-Policy`. A strict Content Security Policy is not enabled because the current interface uses Bootstrap CDN resources and inline styles/scripts; adding CSP requires a nonce/hash refactor and testing.

## Filesystem, Logs, and Protected Content

These directories need runtime write access:

- `assets/uploads/products` or `POS_PRODUCT_UPLOAD_DIR`
- `storage/backups` or `POS_BACKUP_DIR`
- the parent directory of `POS_LOG_PATH`

The root `.htaccess` denies hidden files, configuration, database SQL/migrations, includes, setup/tools/storage paths, logs, backups, and internal Markdown documentation. Upload rules deny executable extensions, while backup and log directories deny all direct HTTP access. Apache protection was verified locally with 403 responses. If the host does not honor `.htaccess`, equivalent virtual-host rules are mandatory.

Application logs must remain outside public access and must not contain passwords, database credentials, session secrets, or raw sensitive request bodies. The project does not implement log rotation; configure operating-system or hosting log rotation, retention, monitoring, and disk-space alerts.

## Product Upload Security

Product images are limited to 5 MB and to JPEG, PNG, or WebP. Validation checks the filename extension, detected MIME type, and actual image structure. Stored names are random 32-character hexadecimal names. Executable extensions are blocked by upload-directory rules. Ensure the configured upload filesystem path and public URL refer to the same directory, and keep script execution disabled there.

CSV import accepts `.csv` files up to 2 MB and 5,000 product rows. It validates MIME type, encoding, formulas, required references, duplicate codes/barcodes, and creates new products only. It does not overwrite existing products.

## Backup and Recovery

Backup/restore is restricted to `backup.manage`, uses CSRF protection, managed filenames, path containment, an operation lock, verified dumps, safe process argument arrays, and temporary MySQL option files so credentials are not placed on the command line. Restore creates a verified pre-restore safety backup before changing the database and invalidates the session afterward.

Production requires server-side `mysqldump` and `mysql` utilities compatible with the database server and an enabled `proc_open`. Set their absolute paths when auto-detection is unsuitable. Store backups outside the document root where possible and limit access to the web-server account and authorized operators.

Recommended operations:

- Run scheduled backups at least daily, with frequency based on acceptable data loss.
- Define daily, weekly, and monthly retention and monitor failures and disk capacity.
- Copy encrypted backups to an off-machine/off-server location.
- Periodically restore a backup into an isolated temporary database and compare tables and critical row counts.
- Never test restore against the live production database.

Pre-deployment safety procedure:

1. Create and verify a database backup.
2. Back up application files and host-level configuration separately.
3. Verify both artifacts exist, are readable, and are stored away from the deployment target.
4. Restore the database backup into an isolated temporary database and validate it.
5. Preserve the prior application release and database backup as the rollback point.
6. If deployment fails, stop traffic, restore the prior code/configuration, restore the database only when required, and rerun smoke tests before reopening service.

## Initial Administrator

There is no public Administrator-creation endpoint and no permanent default credential. Earlier temporary setup scripts were removed. A fresh schema therefore requires an operator-controlled setup step.

Generate a password hash in a trusted CLI environment using PHP `password_hash`, insert one active user through a restricted database administration channel, and link that user to the seeded `Administrator` role in `user_roles`. Do not put the plaintext password in SQL files, command history, source code, tickets, or documentation. Verify login, change the initial password through a controlled process, protect the recovery account, and remove any temporary CLI material before public access is enabled.

For an existing database, verify at least one active Administrator account before deployment. Never expose a web-based setup script.

## Offline POS / PWA

Offline POS requires HTTPS in production; localhost is the browser's development exception. Use a current Chromium-based browser with service workers, IndexedDB, Fetch, and Web Crypto enabled. The application uses cache `sari-pos-offline-v3-1.0.0`, removes older application cache versions, and caches only the Offline POS page plus its intended static CSS, JavaScript, and manifest. It does not cache arbitrary authenticated pages, reports, admin data, passwords, or backups.

Cashiers must sign in online and select **Update Offline Data** before losing connectivity. Cached product and inventory data and pending sales belong to that browser profile/device. Synchronization requires a valid authenticated online session and server-side RBAC, CSRF, product, price, and row-locked stock validation. Duplicate offline transaction IDs are idempotent.

After deployment, confirm the service worker has the expected scope and new cache version. Increment the cache version when changing Offline POS static assets so stale copies are removed.

## Operational Verification

After each deployment:

1. Confirm the database connection, table count, migrations, permissions, charset, foreign keys, and unique indexes.
2. Test login, logout, account, user/role access, master data, products, image upload, import, inventory, suppliers, POS checkout, receipt, reports/CSV, dashboard, backup download, and Offline POS sync.
3. Confirm guest and Staff authorization, CSRF rejection, protected-file 403 responses, security headers, and generic user-facing errors.
4. Confirm inventory deductions/movements, sale totals, historical prices, and offline idempotency.
5. Review PHP/Apache logs and browser console errors without exposing them to users.

## Known Production Limitations

- HTTPS, production TLS certificates, Secure cookies, proxy handling, and HSTS were not tested on the local HTTP XAMPP host.
- Production filesystem ownership/permissions, credentials, least-privilege grants, utility paths, monitoring, and log rotation remain deployment-operator tasks.
- Scheduled and off-machine backups are documented but not configured by the application.
- A strict CSP is not implemented because the UI still uses CDN and inline assets.
- Login throttling and an inactivity timeout are not implemented.
- Safari, Firefox, and physical thermal-printer output were not tested; Chromium and on-screen/print CSS were tested.
- Bootstrap and Bootstrap Icons use CDNs, so the regular online interface needs network access on first load. Offline POS has its own restricted cache.
- Offline data is browser-profile/device-specific and synchronization needs a valid online session.
- Product, inventory, and small master-data management lists are not fully paginated; this is suitable for the intended small-store scale but should be reviewed before using a very large catalog.

## Production Deployment Checklist

- [x] PHP 8.2.4 verified on local XAMPP
- [x] Required PHP extensions/functions verified locally
- [x] Apache syntax and required local modules verified
- [ ] HTTPS enabled and certificate verified on production
- [ ] Production database created
- [ ] Dedicated database user created
- [ ] Strong database password configured
- [ ] Production application environment configured
- [ ] Production filesystem ownership and permissions configured
- [ ] Production upload directory configured and tested
- [ ] Production backup storage configured
- [x] Backup creation and isolated temporary-database restore tested locally
- [ ] Initial production Administrator secured
- [x] Protected files verified locally
- [x] Security headers verified locally over HTTP
- [x] Offline POS service worker and data refresh verified locally in Chromium
- [x] Local smoke and regression tests passed
- [x] Recovery procedure documented

The application is ready for a **controlled production deployment after all unchecked environment-specific items are completed and verified on the target server**.

## Project Structure

```text
pos/
├── assets/                 CSS, JavaScript, images, protected product uploads
├── auth/                   login processing
├── backup/                 authorized backup/restore UI and download
├── config/                 environment-aware configuration and MySQLi connection
├── database/               full schema and apply-once migrations
├── includes/               shared procedural helpers and layout
├── inventory/              inventory management
├── pos/                    online POS, sales history, and Offline POS APIs
├── products/               product management and CSV import
├── receipts/               receipt view/print page
├── reports/                reports and CSV exports
├── storage/                protected runtime backups and logs
├── manifest.webmanifest    Offline POS manifest
├── service-worker.js       versioned restricted Offline POS cache
├── AI_PROGRESS.md          development record
├── DATABASE_STATUS.md      database design/status
└── README.md               setup, deployment, and recovery guide
```

## Development Status

**Phase 18 — Production Readiness is complete.** No Phase 19 is planned.
