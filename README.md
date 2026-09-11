# Sari-Sari Store POS

A procedural PHP Point of Sale system for sari-sari stores and small groceries.

## Project Overview

This application is being built in small, controlled phases. Phase 0 established the project foundation. Phase 1 created the initial database schema. Phase 2 added login, logout, sessions, and basic access control helpers. User management and POS features are not implemented yet.

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
6. Open `http://localhost/pos/` — MySQLi connection should show as successful
7. Create an admin account (CLI only): `C:\xampp\php\php.exe setup\create_admin.php`
8. Log in at `http://localhost/pos/login.php`
9. Remove or disable `setup/create_admin.php` after initial setup

## Project Structure

```
pos/
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── uploads/
├── config/
│   ├── config.php
│   ├── constants.php
│   └── database.php
├── database/
│   ├── database.sql
│   └── migrations/
├── includes/
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   ├── navbar.php
│   └── sidebar.php
├── index.php
├── AI_PROGRESS.md
├── DATABASE_STATUS.md
└── README.md
```

## Current Development Phase

**Phase 2 — Authentication** (complete)

Next: **Phase 3 — Admin / User Management**
