# BrightCV Setup, Testing, and Rebuild Guide

This guide explains how a new contributor can download, configure, run, test,
change, and prepare the rebuilt BrightCV/LunettiStar application for
production. The primary instructions target Windows 11, VS Code, and WAMP.

## 1. Understand the application

BrightCV is a server-rendered PHP application with a MySQL or MariaDB database.
Its browser assets are plain JavaScript and CSS, so the application does not
need a frontend compilation step to run. Node.js is used only for the automated
quality and browser-renderer tests.

Use the repository's `agent/production-cv-builder` branch until that branch has
been merged into `main`.

## 2. Install the required software

Install:

- Git
- Visual Studio Code
- WAMP with PHP 8.1 or newer and MySQL or MariaDB
- Node.js 20 or newer for development checks
- Composer only if you want an optimized production autoloader

Confirm Git, Node.js, and npm are available in PowerShell:

```powershell
git --version
node --version
npm --version
```

Find the PHP versions installed by WAMP:

```powershell
Get-ChildItem C:\wamp64\bin\php -Directory
```

This guide uses PHP 8.2.0. If WAMP shows a different folder, replace
`php8.2.0` in every command below with the installed version.

Confirm WAMP's PHP works:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" -v
```

The PHP installation must have PDO MySQL, mbstring, and JSON enabled. Check
them with:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" -m
```

## 3. Clone the correct branch

Keep the repository directly inside `C:\wamp64\www`. Do not clone it inside
another application such as `GhanaRent`.

Open PowerShell and run:

```powershell
Set-Location C:\wamp64\www
git clone https://github.com/baahe0044-netizen/NewArchitecture_BrightCv.git
Set-Location C:\wamp64\www\NewArchitecture_BrightCv
git switch --track origin/agent/production-cv-builder
code .
```

If the local tracking branch already exists, use:

```powershell
git switch agent/production-cv-builder
git pull --ff-only origin agent/production-cv-builder
```

Confirm the current branch:

```powershell
git branch --show-current
```

It should print:

```text
agent/production-cv-builder
```

## 4. Create the local environment file

In the VS Code PowerShell terminal, run:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
code .env
```

Use these local values:

```env
APP_NAME=LunettiStar
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/NewArchitecture_BrightCv/public
APP_KEY=PASTE_A_GENERATED_KEY_HERE

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brightcv_db
DB_USERNAME=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

SESSION_NAME=lunettistar_session
SESSION_LIFETIME=7200
AI_PROVIDER=local
MAIL_DRIVER=log
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=LunettiStar
```

`brightcv_db` is deliberately separate from the older `lunettistar_db`
schema. Do not point the rebuilt application at the legacy database. Keeping
the databases separate prevents old `username`-based tables from conflicting
with the rebuilt account and CV data model.

Never commit `.env`; it contains deployment-specific secrets and is already
ignored by Git.

## 5. Generate `APP_KEY`

Run:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Copy the 64-character result and paste it after `APP_KEY=` in `.env` without
spaces or quotation marks. Each installation should have its own key.

To make `php` available by name in only the current PowerShell terminal, you
may also run:

```powershell
$env:Path = "C:\wamp64\bin\php\php8.2.0;$env:Path"
php -v
```

## 6. Start WAMP and create the database

Start WAMP and wait until its icon is green. Then run:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" database\migrate.php
```

The expected output is:

```text
Database schema "brightcv_db" is ready.
```

The migration creates the database, creates missing tables, and seeds the CV
templates. It can be run again after pulling updates.

If it reports a legacy or incomplete schema, verify that `.env` contains:

```env
DB_DATABASE=brightcv_db
```

Do not drop the old database. Keep it as a backup until any required legacy
content has been exported and migrated separately.

## 7. Install the development tools and run all checks

From the repository root, run:

```powershell
npm ci
npm run check
```

The full check parses all PHP files, checks JavaScript syntax, validates routes
and asset references, runs PHP domain tests, tests the CV renderer and escaping,
and runs a builder DOM smoke test.

You can also run the PHP tests with WAMP's native PHP:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" tests\run.php
```

Do not continue to deployment while a check is failing.

## 8. Open and test the application

Restart all WAMP services after changing `.env`, then open:

```text
http://localhost/NewArchitecture_BrightCv/public
```

Perform this first-run smoke test:

1. Create an account.
2. Sign in and confirm the dashboard opens.
3. Create a CV from a template.
4. Complete personal details, summary, experience, education, and skills.
5. Refresh the page and confirm autosaved data remains.
6. Run the ATS scan with a job description.
7. Try a smart-writing action.
8. Change the template, accent colour, font, and language.
9. Open print preview and save a PDF.
10. Sign out, sign in again, and reopen the CV.

Local password-reset messages are written to:

```text
storage/logs/mail.log
```

## 9. Pull future updates safely

Before pulling, inspect local changes:

```powershell
git status
```

If the tree is clean, update the branch with:

```powershell
git pull --ff-only origin agent/production-cv-builder
```

After an update, run:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" database\migrate.php
npm ci
npm run check
```

Restart WAMP and use `Ctrl + F5` in the browser.

## 10. Make and test a rebuild or feature change

Create a separate branch before editing:

```powershell
git switch -c feature/describe-the-change
```

Work within the existing architecture:

- `app/Controllers`: request and page orchestration
- `app/Core`: bootstrap, routing, database, requests, responses, and views
- `app/Services`: business rules and CV features
- `app/Repositories`: database queries and ownership enforcement
- `app/Middleware`: authentication, guest, CSRF, and admin guards
- `app/Views`: server-rendered pages and partials
- `public/assets`: browser JavaScript, styles, and images
- `config/routes.php`: web and API routes
- `database/schema.sql`: canonical fresh-install schema
- `tests` and `scripts`: automated verification

After every meaningful change, run:

```powershell
npm run check
& "C:\wamp64\bin\php\php8.2.0\php.exe" tests\run.php
```

Then inspect and commit only the intended files:

```powershell
git status
git diff
git add path\to\changed-file another\changed-file
git commit -m "Describe the completed change"
git push -u origin feature/describe-the-change
```

Open a pull request on GitHub. The change should be reviewed and tested before
it is merged into the production branch.

There is no `npm run build` requirement for this repository. PHP, CSS, and
JavaScript source files are served directly. `npm run check` is the required
quality gate.

## 11. Rebuild with an entirely fresh local database

To test a clean installation without deleting anything, choose a new database
name in `.env`, for example:

```env
DB_DATABASE=brightcv_rebuild_test_db
```

Run the migration again:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" database\migrate.php
```

Restart WAMP and repeat the complete smoke test. Changing the database name is
safer than dropping an existing database and makes the rebuild reproducible.

Change `.env` back to `brightcv_db` when the clean-install test is complete.

## 12. Common problems

### `php` is not recognized

Use the full WAMP executable path:

```powershell
& "C:\wamp64\bin\php\php8.2.0\php.exe" -v
```

### `Class "App" not found` or `Class "Database" not found`

Confirm the production branch is current:

```powershell
git switch agent/production-cv-builder
git pull --ff-only origin agent/production-cv-builder
```

Restart all WAMP services afterward.

### `Unknown column 'name' in 'field list'`

The application is connected to the old `lunettistar_db`. Set this in `.env`:

```env
DB_DATABASE=brightcv_db
```

Run `database\migrate.php` again and restart WAMP.

### Every route except the home page returns 404

Enable Apache `mod_rewrite`, ensure `AllowOverride All` applies to the WAMP
`www` directory, and restart Apache.

### Styles or links point to the wrong folder

Confirm the local URL is exact and has no trailing slash:

```env
APP_URL=http://localhost/NewArchitecture_BrightCv/public
```

### Database connection is refused

Confirm WAMP is green, MySQL is running, and `DB_HOST`, `DB_PORT`,
`DB_USERNAME`, and `DB_PASSWORD` match the local WAMP configuration.

## 13. Production deployment checklist

Before publishing publicly:

1. Point the web-server document root directly to the repository's `public`
   directory.
2. Use HTTPS and redirect all HTTP requests to HTTPS.
3. Set `APP_ENV=production` and `APP_DEBUG=false`.
4. Set the exact HTTPS `APP_URL` and generate a unique production `APP_KEY`.
5. Use a dedicated database and a least-privilege database user, not `root`.
6. Back up the database and private storage before every migration.
7. Configure a real mail transport and change `MAIL_DRIVER` from `log`.
8. Make only `storage/cache`, `storage/logs`, `storage/pdfs`, and
   `storage/uploads` writable by the web process.
9. Keep `.env`, `app`, `config`, `database`, `storage`, and `tests` outside the
   public web root.
10. Run `npm ci`, `npm run check`, and `php database/migrate.php` against the
    release commit.
11. Verify registration, login, password reset, CV autosave, ATS analysis,
    template changes, and PDF printing on the production hostname.
12. Configure recurring database and private-file backups and test a restore.

For more detail, see `README.md`, `docs/Installation.md`,
`docs/Database.md`, and `docs/Deployment.md`.
