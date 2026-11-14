# UniStay 🏠

**Find a boarding place near your university.**

UniStay is a university accommodation discovery and management web application. Students can browse boarding places, send inquiries, confirm tenancies, and view rent history. Boarding owners manage their properties, photos, inquiries, tenants, and rent records. An admin can manage users, listings, and universities.

This repository contains a **PHP application**, HTML/CSS/JavaScript frontend, and a **TiDB Cloud (MySQL-compatible)** database connection. Property images are stored in **Cloudinary**, and Google Sign-In is supported when configured.

> **Local development URL:** http://localhost:8000/public/index.php  
> **Run from the repository root:** `php -S localhost:8000 -t frontend`  
> **No XAMPP or local MySQL server is required** when using the team's TiDB Cloud database.

---

## Table of contents

1. [Requirements](#1-requirements)
2. [Clone the repository](#2-clone-the-repository)
3. [Install PHP dependencies](#3-install-php-dependencies)
4. [Configure the TiDB database](#4-configure-the-tidb-database)
5. [Set up the database schema](#5-set-up-the-database-schema)
6. [Configure Cloudinary (property photos)](#6-configure-cloudinary-property-photos)
7. [Configure Google Sign-In](#7-configure-google-sign-in)
8. [Run UniStay on localhost](#8-run-unistay-on-localhost)
9. [Create an admin account (authorized maintainers)](#9-create-an-admin-account-authorized-maintainers)
10. [Pages and project structure](#10-pages-and-project-structure)
11. [Team development workflow](#11-team-development-workflow)
12. [Troubleshooting](#12-troubleshooting)
13. [Security checklist](#13-security-checklist)

## 1. Requirements

Install the following on your computer:

| Tool / service | Purpose |
| --- | --- |
| **Git** | Clone and update the code |
| **PHP 8.5.x** (recommended; match the team's working version) | Run the application and development server |
| **Composer** | Install PHP packages from `composer.json`, including Google authentication dependencies |
| **TiDB Cloud** database access | Shared MySQL-compatible database |
| **Cloudinary account/config** | Upload and display boarding photos (optional for basic browsing; required for uploads) |
| **Google OAuth Web Client ID** | Google Sign-In (optional for email/password login) |
| A modern browser | Open and test the site |

Enable the following PHP extensions: **`pdo_mysql`**, **`curl`**, **`openssl`**, and **`fileinfo`**. Some dependency packages may also require `mbstring` or other extensions; Composer will report these during installation.

Check your installation in PowerShell:

```powershell
php -v
php -m
composer --version
git --version
```

> **Windows note:** Use the PHP installation on your `PATH` (the team has used PHP 8.5.x). The project relies on a PHP version with the `\Pdo\Mysql` SSL options used in `backend/includes/database.php`. For local work, using the same PHP version as other developers avoids environment differences.

## 2. Clone the repository

Open **PowerShell** in the directory where you keep your projects:

```powershell
git clone https://github.com/baby-sharks1/UNISTAY.git
cd UNISTAY
git switch main
git pull --ff-only origin main
```

If you already cloned the project:

```powershell
cd "C:\path\to\UNISTAY"
git status
git switch main
git pull --ff-only origin main
```

If Git reports local changes, **commit or stash your own work before switching branches**. Do not force-reset another teammate's work.

## 3. Install PHP dependencies

From the **repository root** (the directory containing `frontend/`, `backend/`, and `config/`):

```powershell
composer install
```

Use `composer install` when `composer.json` and `composer.lock` are present; it installs the versions specified by the repository. If Composer isn't installed, install Composer first. If your checkout does not contain `composer.json`, ask the authentication maintainer whether Composer dependencies are needed for the version of UniStay you pulled.

The generated `vendor/` folder should be **kept locally and not committed**.

## 4. Configure the TiDB database

The PHP backend loads its database settings through:

```text
backend/includes/database.php
             ↓
config/database.local.php
```

**`config/database.local.php` is private and is not downloaded automatically from GitHub.** Each teammate must create it locally using authorized TiDB credentials supplied securely by the database maintainer.

Create the file inside the **root `config/` directory**:

```powershell
New-Item -ItemType Directory -Force config | Out-Null
notepad config/database.local.php
```

Add the following PHP structure and **replace all placeholders locally**:

```php
<?php
return [
    'host'     => 'YOUR_TIDB_HOST',
    'port'     => 4000,
    'database' => 'UNISTAY',
    'username' => 'YOUR_TIDB_USERNAME',
    'password' => 'YOUR_TIDB_PASSWORD',
    'ssl_ca'   => 'C:/path/to/your/trusted/ca.pem',
];
```

The `ssl_ca` value **must point to an existing trusted CA certificate file**, obtained from your TiDB Cloud connection setup/provider. Use forward slashes in Windows PHP paths or an absolute path appropriate to your operating system. **Do not disable TLS certificate verification.**

Check the required file and syntax:

```powershell
Test-Path config/database.local.php
php -l config/database.local.php
```

To test the actual database connection, run this PowerShell block from the repository root (it avoids `php -r` quoting issues):

```powershell
@'
<?php
require getcwd() . '/backend/includes/database.php';
try {
    db()->query('SELECT 1');
    echo "SUCCESS: Connected to TiDB.\n";
} catch (Throwable $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
'@ | php
```

If your team's checkout still has older pages that use `config/db_connect.php`, coordinate with the database maintainer so **both connectors use the same authorized database safely**. Do not insert plaintext credentials into a Git-tracked connector.

## 5. Set up the database schema

UniStay uses a **shared remote TiDB database**, not a fresh database created automatically when PHP starts.

### For teammates joining the existing database

If the database maintainer has already created and migrated the tables, **do not run schema-creation scripts again**. Just configure `database.local.php` and use your authorized credentials.

### For a new/test database

Ask the database maintainer for the **current base schema** and apply it in the **TiDB Cloud SQL Editor** first. Then review only the required migrations in `database/migrations/`. These migrations may already have been applied to the shared database.

Important database objects include:

```text
User                  University
BoardingListing       ListingPhoto
Inquiry               Tenancy
RentPayment
```

UniStay uses case-sensitive names in code such as **`User`** and **`BoardingListing`**. Old names like `users`, `boarding_listings`, or `universities` are not interchangeable with the established TiDB schema.

Some insert handlers rely on sequences, including:

```text
unistay_user_seq
unistay_listing_seq
unistay_inquiry_seq
unistay_tenancy_seq
```

If one is missing, **coordinate with the database maintainer** before creating it. When creating a new sequence, its starting value must be higher than all existing IDs in that table to avoid collisions. Never blindly recreate or reset sequences in the shared database.

Where available, run the project's read-only diagnostics:

```powershell
php scripts/preflight.php
```

> **Note:** Some older diagnostic scripts may still check for the removed legacy `frontend/login.php` page. The canonical login URL is now `/public/login.php`; an obsolete check should not cause you to restore a duplicate login page.

## 6. Configure Cloudinary (property photos)

Cloudinary stores uploaded boarding images; TiDB stores their URLs/metadata.

If available, copy the example file:

```powershell
Copy-Item config/cloudinary.example.php config/cloudinary.local.php
notepad config/cloudinary.local.php
```

If that example file is not present, create `config/cloudinary.local.php` with:

```php
<?php
return [
    'cloud_name' => 'YOUR_CLOUD_NAME',
    'api_key' => 'YOUR_API_KEY',
    'api_secret' => 'YOUR_API_SECRET',
    'force_ipv4' => false,
    'ca_bundle' => '',
];
```

Use only the credentials provided for the team's authorized Cloudinary environment. The `api_secret` is **server-side only**; never place it in JavaScript or HTML. Leave TLS verification enabled. Cloudinary setup is needed to upload/manage listing images.

## 7. Configure Google Sign-In

The login interface is available at `/public/login.php`; the server verifies Google credentials in `/api/google-login.php`.

Create the private file `config/google.local.php` if it is required by the current authentication implementation:

```php
<?php
return [
    'client_id' => 'YOUR_GOOGLE_OAUTH_WEB_CLIENT_ID.apps.googleusercontent.com',
];
```

Ask the authentication maintainer for the **correct Google OAuth Web Client ID** and any additional server-side settings used by the current backend. In the Google Cloud OAuth client settings, register **`http://localhost:8000`** as an authorized JavaScript origin for local development when using Google Identity Services. Google Sign-In will not work from arbitrary origins.

Email/password authentication can be used without configuring Google Sign-In, provided the account and database setup are valid.

Do **not** put OAuth client secrets or credentials in GitHub. A Google Web Client ID may be visible to the browser, but any server-side secrets must stay private.

## 8. Run UniStay on localhost

From the **repository root**, run:

```powershell
php -S localhost:8000 -t frontend
```

Keep that terminal open while testing. Open the following URLs in your browser:

| Page | URL |
| --- | --- |
| **Homepage** | **http://localhost:8000/public/index.php** |
| Browse boarding places | http://localhost:8000/public/listings.php |
| Login (single public login) | http://localhost:8000/public/login.php |
| Register | http://localhost:8000/public/register.php |
| Student dashboard (login required) | http://localhost:8000/student/dashboard.php |
| Owner dashboard (login required) | http://localhost:8000/owner/dashboard.php |
| Admin dashboard (authorized admin only) | http://localhost:8000/public/admin/dashboard.php |

Use an actual listing ID for a property details URL:

```text
http://localhost:8000/public/listing.php?id=YOUR_LISTING_ID
```

And for inquiries:

```text
http://localhost:8000/public/inquiry.php?listing_id=YOUR_LISTING_ID
```

**Important:** The `frontend/` directory is the web server's document root. Browser assets should use paths like `/src/styles/style.css`, **not** `/UNISTAY/frontend/src/styles/style.css`. PHP `require`/`include` paths on disk are different from browser URLs.

For image uploads with a larger request body, you may need:

```powershell
php -d upload_max_filesize=5M -d post_max_size=24M -S localhost:8000 -t frontend
```

Stop the server with **Ctrl+C**. To change the port, replace `8000` in the command and URLs; also update the authorized Google OAuth origin if applicable.

## 9. Create an admin account (authorized maintainers)

**Do not add an `admin` role option to public registration.** Admins use the normal login page, but their accounts are provisioned separately.

If `scripts/provision_admin.php` is available and you have permission to manage the admin account, run:

```powershell
$env:UNISTAY_ADMIN_EMAIL = Read-Host 'Admin email'
$securePassword = Read-Host 'Admin password (14+ characters)' -AsSecureString
$env:UNISTAY_ADMIN_PASSWORD = (
    New-Object System.Net.NetworkCredential('', $securePassword)
).Password

try {
    php scripts/provision_admin.php
    Write-Host "Exit code: $LASTEXITCODE"
} finally {
    Remove-Item Env:\UNISTAY_ADMIN_EMAIL -ErrorAction SilentlyContinue
    Remove-Item Env:\UNISTAY_ADMIN_PASSWORD -ErrorAction SilentlyContinue
    Remove-Variable securePassword -ErrorAction SilentlyContinue
}
```

The provisioning tool is designed for **one authorized admin account**; follow the maintainer's policy. It expects the `User` table and `unistay_user_seq` sequence to exist. The account password is hashed in the database, not hardcoded into login PHP. Some versions of this tool create `config/admin.local.php` containing only the permitted admin email—**still keep it untracked**.

After a successful setup, sign in through:

```text
http://localhost:8000/public/login.php
```

Your admin account should be routed to `/public/admin/dashboard.php`.

## 10. Pages and project structure

The important folders are:

```text
UNISTAY/
├── backend/
│   └── includes/           # Database, authentication and business logic
├── config/                 # Local credentials and shared config examples
├── database/
│   └── migrations/         # SQL schema updates (review before applying)
├── docs/                   # Additional feature and integration notes
├── frontend/               # PHP development-server document root
│   ├── public/             # Public entry points: home, login, register, listings, inquiry
│   │   └── admin/          # Admin entry points
│   ├── src/
│   │   ├── pages/          # Team UI templates and admin pages
│   │   ├── components/     # Reusable frontend elements
│   │   └── styles/         # Frontend CSS
│   ├── api/                # Auth/API endpoints
│   ├── student/            # Student module
│   ├── owner/              # Owner module
│   └── assets/             # Shared assets and module styles
├── scripts/                # Preflight, setup and maintenance scripts
├── composer.json           # PHP dependency definition (when present)
└── README.md
```

Some repository files and optional scripts may differ depending on the latest `main` commit. **Use the checked-out code as the source of truth**, and ask the relevant feature maintainer when an example or migration is missing.

### Main user flows

- **Visitor:** Homepage → Browse listings → View details → Sign in to send inquiry.
- **Student:** Login → Submit inquiry → Check approval → Confirm tenancy → View payments.
- **Owner:** Login → Create listing → Upload photos → Review inquiries → Manage tenants and rent.
- **Admin:** Login → View dashboard → Manage users, listings, and universities.

## 11. Team development workflow

Always pull latest `main` before starting work:

```powershell
git switch main
git pull --ff-only origin main
```

Create a **feature branch**, for example:

```powershell
git switch -c feature/my-page-update
```

After making and testing changes:

```powershell
git status
git diff --check
git add path/to/your/changed-file.php
git diff --cached --name-only
git diff --cached --check
git commit -m "Describe the feature or fix"
git push -u origin feature/my-page-update
```

Open a pull request into `main` and ask another teammate to review. Avoid direct pushes to `main` unless your team explicitly agreed to them. **Never run `git add -A` without reviewing untracked files**, especially credentials and uploaded content.

For a routine sync later:

```powershell
git switch main
git pull --ff-only origin main
```

## 12. Troubleshooting

| Symptom | What to check |
| --- | --- |
| `Database configuration file is missing` | Create `config/database.local.php` in the **repository root** `config/` directory. |
| `TiDB CA certificate is missing` | Correct the `ssl_ca` path, ensure the file exists, and keep certificate verification enabled. |
| `SQLSTATE[42S02] ... users doesn't exist` | This project uses **`User`**, not `users`. Check other table names and SQL columns as well. |
| `unistay_user_seq doesn't exist` | Ask the DB maintainer to create/repair the missing sequence safely, starting beyond existing user IDs. |
| CSS returns 404 / page unstyled | Use `/src/styles/style.css` instead of paths including `/UNISTAY/frontend/`; see browser DevTools → Network. |
| Google login returns `403 Session expired` | Refresh `/public/login.php`; confirm PHP sessions and matching CSRF token submission. Do not disable CSRF protection. |
| Google sign-in not shown | Check `config/google.local.php`, Composer dependencies, OAuth origin `http://localhost:8000`, and browser console. |
| Upload gives TLS/cURL or Cloudinary 401 error | Check private Cloudinary configuration, matching API credentials, and trusted CA bundle. |
| `Class PDO` / `could not find driver` | Enable `pdo_mysql` in the PHP installation actually being used (`php -m`). |
| Homepage works but protected page redirects | Sign in using the appropriate role; students cannot open owner/admin pages. |
| `Port 8000 already in use` | Stop the previous server or use another port and adjust the local URL. |
| `php -r` parsing errors on PowerShell | Use a temporary PHP file or the PowerShell here-string example in [database setup](#4-configure-the-tidb-database). |

When requesting help, include the **error message, PHP server log, exact URL, and the relevant PHP file**—but redact secrets, session cookies, authorization headers, and Google tokens.

## 13. Security checklist

Local configuration files must not be committed:

```gitignore
/config/database.local.php
/config/cloudinary.local.php
/config/google.local.php
/config/admin.local.php
/config/certs/
/vendor/
/frontend/uploads/listings/*
!/frontend/uploads/listings/.gitkeep
```

Check the actual repository `.gitignore` before staging; these are recommended rules, **not proof that they are already in place**.

Verify ignoring locally:

```powershell
git check-ignore -v config/database.local.php config/cloudinary.local.php config/google.local.php config/admin.local.php
```

- Never commit TiDB passwords, Cloudinary API secrets, Google OAuth client secrets, password hashes, or live student data.
- If a secret was committed previously, removing the file from the latest commit **does not erase Git history**. Rotate the exposed secret.
- Keep role-based authorization and CSRF validation on all protected routes.
- Do not turn off TLS verification to silence HTTPS errors.
- The PHP built-in server is for **local development only**, not production hosting.
- Coordinate before testing changes that write to the **shared TiDB database**. Prefer dedicated test records or a separate test database.

---

**UniStay team:** Keep `README.md` updated when setup steps, required migrations, configuration names, or public routes change. This makes onboarding new contributors much easier.
