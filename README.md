# Sari-Sari Store POS

A procedural PHP Point of Sale system for sari-sari stores and small groceries.

## Project Overview

This application is being built in small, controlled phases. It currently includes authentication and RBAC, product master data, inventory, basic POS and sales, receipts, suppliers, advanced product management, operational reports, a permission-aware dashboard, and Administrator database backup/restore.

## Technology Stack

- PHP 8.x (procedural)
- MySQL
- MySQLi (no PDO)
- HTML5, CSS3, JavaScript
- Bootstrap 5 (CDN)
- AJAX / Fetch API (future phases)

**Not used:** Laravel, CodeIgniter, PDO, npm, Node.js, React, Vue, or any PHP framework.

## Requirements

- XAMPP (Apache + MySQL + PHP 8.x)
- Web browser

## XAMPP Setup

1. Place the project in `C:\xampp\htdocs\pos`
2. Start **Apache** and **MySQL** from the XAMPP Control Panel
3. Open `http://localhost/pos/` in your browser
4. Database credentials are in `config/constants.php` (default XAMPP: `root` with no password)
5. Import the database: `C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\pos\database\database.sql`
6. Open `http://localhost/pos/` — unauthenticated users are redirected to login
7. Ensure `assets/uploads/products/` is writable by Apache for product image uploads
8. Log in at `http://localhost/pos/login.php` with an existing administrator account

## Backup and Restore Requirements

- PHP `proc_open` must be enabled.
- XAMPP must provide `mysqldump` and `mysql` under its MySQL `bin` directory.
- Apache must be able to write to `storage/backups/`.
- The included `.htaccess` denies direct browser access to stored SQL files.

## Administrator Backup and Restore

1. Sign in as an Administrator with `backup.manage`.
2. Open **Backup & Restore** from the sidebar.
3. Select **Create Backup** to generate and verify an SQL dump.
4. Use **Download** to retrieve a backup through the authorized application endpoint.
5. To restore, expand the selected backup, type `RESTORE`, acknowledge the warning, and submit.

Restoring replaces the current database state. The application first creates a fresh `pre_restore_*.sql` safety backup and stops without restoring if that backup fails. A failed restore keeps the safety backup for manual recovery. After a successful restore, the current session is invalidated and the Administrator must sign in using credentials from the restored database.

## Project Structure

```
pos/
├── backup/
│   ├── download.php
│   ├── index.php
│   └── process.php
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/
│       └── products/
├── config/
│   ├── config.php
│   ├── constants.php
│   └── database.php
├── database/
│   ├── database.sql
│   └── migrations/
├── includes/
│   ├── backup.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   ├── navbar.php
│   └── sidebar.php
├── storage/
│   └── backups/
├── index.php
├── AI_PROGRESS.md
├── DATABASE_STATUS.md
└── README.md
```

## Current Development Phase

**Phase 13 — Backup/Restore** (complete)

Authorized users can open `http://localhost/pos/reports/` for sales, inventory, movement, supplier stock-in, and stock-status reports. The main tabular reports support filtered CSV export.

After login, `http://localhost/pos/` displays dashboard sections and quick actions allowed by the user's existing permissions. Administrators can manage database backups at `http://localhost/pos/backup/`.

Next: **Phase 14 — Offline POS**
