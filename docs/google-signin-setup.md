# UniStay Google sign-in setup (PHP + TiDB)

This implementation keeps the existing email/password sign-in and adds the
Google Identity Services button. It uses **no `.env` file**.

## 1. Install the server-side verifier

From the repository root in PowerShell:

```powershell
composer install
```

If Composer is not installed, install it first from https://getcomposer.org/.
The PHP library is `google/apiclient`. Commit `composer.lock` after installation;
do **not** commit `vendor/`.

## 2. Configure the public Google Client ID

```powershell
Copy-Item config/google.example.php config/google.local.php
notepad config/google.local.php
```

Replace the placeholder with the Google OAuth **Web application Client ID**.
The Authorized JavaScript origin in Google Cloud must be exactly
`http://localhost:8000` for this local setup. A redirect URI is not required
for this JavaScript-callback flow. You do not need a Google Client Secret.

Do not put the TiDB database password into this Google config.

## 3. Coordinate the users table with the database team

The backend expects these columns in `users`:

| Column | Expected |
| --- | --- |
| `user_id` | numeric primary key with auto-generated value |
| `full_name` | text |
| `email` | unique email |
| `password_hash` | password hash string, at least VARCHAR(255) |
| `role` | allows `admin`, `owner`, `student` |
| `status` | allows `active`, `suspended` |
| `google_sub` | nullable unique string (add with migration) |
| `created_at` | default timestamp if present |

After the database team confirms the current table, run the SQL in
`database/migrations/2026_11_08_google_signin.sql` **once**.
If any required columns or enum values differ, coordinate the adjustment
before testing or running migrations. The migration is not automatic.

For security, a Google-only account receives a random unusable password hash
to support a NOT NULL `password_hash` field.

An existing password account with the same email is not silently linked
to a Google identity. The user must use the existing password flow for now;
account linking can be implemented with explicit reauthentication later.

## 4. Run locally

```powershell
php scripts/test-db.php
php -S localhost:8000 -t frontend
```

Open http://localhost:8000/login.php, choose Student or Property owner
(only used for a *new* Google account), and click Sign in with Google.
Verified accounts are stored in TiDB; subsequent logins use the saved role.
Google does **not** let users self-register as administrators.

## 5. Security action before GitHub push

The repository previously tracked `config/database.local.php` and had
database connection credentials directly in `config/db_connect.php`. The
new `config/db_connect.php` delegates to `backend/includes/database.php`.
The tracked local config must be removed **from Git's index**, while keeping
each teammate's private local copy:

```powershell
git rm --cached config/database.local.php
git add .gitignore config/db_connect.php
```

The `.gitignore` now excludes `config/database.local.php`,
`config/google.local.php`, and `vendor/`.

**Rotate the TiDB database password immediately.** Removing files in a new
commit does not erase their contents from Git history. Coordinate any
history cleanup with teammates before rewriting shared Git history.

## Production

Add the deployed HTTPS site as a new Authorized JavaScript origin in Google
Cloud and configure the production Client ID on the server. Ensure the
production PHP session cookie is Secure and the frontend is served over HTTPS.
Use a separate Google Cloud project/client for production as recommended by
Google, and complete applicable Google brand/policy verification.
