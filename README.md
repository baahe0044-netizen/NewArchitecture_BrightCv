# BrightCV CV Builder

BrightCV is a production-oriented PHP and MySQL CV builder. It combines a
focused three-panel writing workspace with live A4 preview, applicant tracking
system guidance, job-description matching, smart writing support, and secure
account-based storage.

For complete beginner-friendly installation, testing, rebuild, troubleshooting,
and production steps, see [SETUP_AND_REBUILD_GUIDE.md](SETUP_AND_REBUILD_GUIDE.md).
For ready-to-paste Windows PowerShell commands, see
[COPY_PASTE_SETUP_COMMANDS.md](COPY_PASTE_SETUP_COMMANDS.md).

## What is included

- Secure registration, sign-in, logout, password reset, CSRF protection, and
  login throttling
- Personal dashboard with CV status, completion, ATS trends, exports, and
  recent activity
- Six responsive, print-ready CV templates
- Guided sections for contact details, summary, experience, education, skills,
  projects, certifications, languages, references, and interests
- Live A4 preview with colour, typeface, density, layout, and language controls
- Debounced server autosave, local offline recovery, undo and redo, immutable
  version snapshots, duplication, and soft deletion
- Rule-based smart summary and achievement writing that does not send private
  CV content to an external provider
- ATS scoring, content recommendations, keyword extraction, and target-job
  matching
- Voice dictation where the browser supports the Web Speech API
- Print-to-PDF, multilingual section headings, and portable JSON backup/import
- Automated PHP parsing, JavaScript syntax checks, repository integrity checks,
  PHP domain tests, renderer unit tests, and a builder UI smoke test

## Requirements

- PHP 8.1 or newer with PDO MySQL, mbstring, and JSON
- MySQL 8 or MariaDB 10.5 or newer
- Apache with mod_rewrite enabled, or another web server configured to route
  requests to public/index.php
- Node.js 20 or newer only when running the development quality checks

The runtime does not require Node.js. Composer is supported for optimized class
loading but the application also has a safe fallback autoloader.

## Quick setup with WAMP

1. Place the repository inside C:\wamp64\www.
2. Copy .env.example to .env.
3. Set APP_URL to the exact public URL for the folder. For example:

       APP_URL=http://localhost/NewArchitecture_BrightCv/public

4. Keep the dedicated default database (`DB_DATABASE=brightcv_db`). An earlier
   LunettiStar installation may already use `lunettistar_db`, which has an
   incompatible legacy structure.
5. Create the schema using either option:

       php database/migrate.php

   If PHP is not on PATH, use your WAMP executable:

       C:\wamp64\bin\php\php8.2.0\php.exe database\migrate.php

   You can instead import database/schema.sql through phpMyAdmin.
6. Open:

       http://localhost/NewArchitecture_BrightCv/public

If the folder is named LunettiStar, change APP_URL accordingly.

## Quality checks

Install development-only packages, then run the full suite:

    npm install
    npm run check

The suite parses every PHP file, checks every JavaScript file, verifies route,
view, partial, and asset references, executes PHP domain tests using a bundled
WebAssembly PHP runtime, tests the CV renderer, and exercises the live builder
in a browser-like DOM.
On a machine with PHP installed, the domain tests can also be run directly:

    php tests/run.php

## Production launch

Before a public deployment:

- Point the web server document root to the public directory.
- Use APP_ENV=production and APP_DEBUG=false.
- Set APP_KEY to a long, deployment-specific random secret.
- Serve the site only over HTTPS.
- Use a dedicated database account with the minimum required privileges.
- Set MAIL_DRIVER=mail and configure the host PHP mail transport, or replace
  MailService with the deployment provider.
- Make storage/logs, storage/cache, storage/uploads, and storage/pdfs writable
  by the web process but never publicly accessible.
- Back up the database and storage directory on a tested schedule.
- Run npm run check and php database/migrate.php as part of deployment.

See docs/Installation.md and docs/Deployment.md for full details.

## Main project structure

    app/
      Controllers/    HTTP and page orchestration
      Core/           router, request, response, auth, CSRF, view
      Middleware/     authentication, guest, admin, CSRF guards
      Repositories/   database queries and ownership boundaries
      Services/       business rules, ATS analysis, writing, accounts
      Views/          server-rendered pages
    config/           environment bootstrap and routes
    database/         idempotent schema and migration command
    public/           the only web-accessible entry point and assets
    storage/          private generated data, logs, and temporary files
    tests/            domain and browser-renderer tests

## Data model

Each CV is stored as a validated JSON document in resumes.content_json. The
record also stores layout preferences, completion, ATS score, status, and an
incrementing version. Periodic immutable snapshots are kept in resume_versions.
This model makes dynamic sections easy to reorder and export while ownership
and audit data remain relational.

## License

This repository is currently private application code. Add an explicit license
before distributing it outside the project team.
