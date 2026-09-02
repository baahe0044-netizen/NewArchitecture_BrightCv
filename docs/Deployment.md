# Production deployment

## Web server

The document root must be the repository’s public directory. Never expose app,
config, database, storage, tests, or vendor. A defence-in-depth root .htaccess
blocks those directories when the parent project is accidentally web-visible.

Route all non-file requests to public/index.php and enable HTTPS redirects at
the server or reverse proxy.

## Environment

Use:

    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://your-domain.example
    APP_KEY=a-long-random-secret
    MAIL_DRIVER=mail

Generate a unique APP_KEY for each deployment, use a dedicated database
credential rather than root, and never commit .env.

## Filesystem

The web process needs write permission only for:

- storage/cache
- storage/logs
- storage/pdfs
- storage/uploads

All other project files should be read-only to the web process.

## Deployment sequence

1. Back up the database and private storage.
2. Deploy the tested commit.
3. Install optimized Composer dependencies if Composer is used.
4. Run php database/migrate.php.
5. Clear storage/cache.
6. Verify registration, login, autosave, ATS scan, password reset, and PDF
   printing on the production hostname.
7. Confirm APP_DEBUG is false and private paths return 403 or 404.

## Shared and cPanel hosting

BrightCV is a plain PHP and MySQL application. It shells out to nothing, needs
no background worker, no headless browser, and no Node runtime at run time, so
an ordinary cPanel or LAMP plan runs it. Node and Composer are only used by the
development tooling and can stay off the server entirely.

What the host must provide:

| Requirement | Detail |
| --- | --- |
| PHP | 8.1 or newer |
| PHP extensions | json, mbstring, pdo, pdo_mysql, session, zlib |
| Database | MySQL 5.7+ or MariaDB 10.3+, one database and one user |
| Web server | Apache with mod_rewrite and `.htaccess` honoured (`AllowOverride All`), or nginx with an equivalent rewrite |
| Document root | pointed at the repository's `public/` directory |
| Uploads | `upload_max_filesize` and `post_max_size` of 5M or more, for CV import |
| Mail | An HTTPS provider API (`MAIL_DRIVER=api`, works everywhere), or SMTP or `mail()` on a plan that permits them |
| TLS | any certificate; a free one is fine |

Optional:

- `zip` enables importing a CV from a Word `.docx` file. Without it, PDF, plain
  text, and pasted text import still work and the app says so plainly.
- `gd` is only needed to regenerate the app icons with
  `scripts/generate-icons.php`. The icons are committed, so the server does not
  need it.

Not needed: shell access, `exec`, cron, a queue, Composer, Node, Docker, or a
PDF binary. Exporting a PDF uses the browser's own print engine.

### Steps on a cPanel plan

1. Create the database and a user, and grant that user all privileges on it.
2. Upload the repository, or deploy it from Git if the host offers that.
3. Point the domain or subdomain's document root at `public/`. On cPanel this
   is Domains → the domain → Document Root. If the plan will not let you move
   the document root, the app still works from `https://your-domain/public/`;
   set `APP_URL` to match, including the `/public`.
4. Copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false`,
   `APP_URL`, a long random `APP_KEY`, the database credentials, and the mail
   settings. `MAIL_DRIVER=api` with a provider key is the option that works on
   every host; use `smtp` or `mail` only where the plan allows them.
5. Give the web user write access to `storage/cache`, `storage/logs`,
   `storage/pdfs`, and `storage/uploads`. Everything else can be read-only.
6. Create the schema. With shell access run `php database/migrate.php`;
   without it, import `database/schema.sql` through phpMyAdmin.
7. Run `php scripts/doctor.php` if you have shell access. It checks the PHP
   version, the extensions, the upload limits, `APP_URL`, `APP_KEY`, the
   writable directories, and the database schema, and names anything missing.
8. Open the site and confirm registration, sign-in, autosave, CV import, and
   Print / PDF.

### Free hosting, and InfinityFree in particular

BrightCV runs on a free plan, with one thing to arrange first: **email**. Free
hosts disable PHP's `mail()` and block outbound connections on the SMTP ports
(465 and 587), so both of those transports time out. Port 443 is always open,
because the site itself is served over it, which is why `MAIL_DRIVER=api` is
the default for production. Without it, password reset fails silently — the
person waiting for the message simply never gets one.

Checked against InfinityFree's free plan:

| What BrightCV needs | InfinityFree | |
| --- | --- | --- |
| PHP `^8.1` | 8.3 selectable | fine |
| MySQL | 5.6 | fine — `email VARCHAR(190)` keeps the unique index inside the 767-byte limit that older InnoDB row formats impose |
| Database size | 50 MB per database | fine — a stored CV averages about 3 KB, so roughly 15,000 CVs including version history |
| Apache `.htaccess` | honoured | clean URLs work |
| TLS | free certificate | required: the service worker will not register over plain HTTP, so the PWA needs it |
| Traffic | 30,000 hits/day | fine for a portfolio or an early user base |
| `mail()` | disabled | use `MAIL_DRIVER=api` |
| Outbound SMTP | blocked | `MAIL_DRIVER=smtp` will not work here |

Setting up mail:

1. Create a free account with Brevo or Resend and verify a sending domain.
   The address in `MAIL_FROM_ADDRESS` has to be on that domain; a provider will
   not send as `@example.com` or as a Gmail address you do not control.
2. Set `MAIL_DRIVER=api`, `MAIL_API_PROVIDER` to `brevo` or `resend`, and
   `MAIL_API_KEY` to the key the provider issues.
3. Trigger a password reset and confirm the message arrives. If it does not,
   `storage/logs/mail.log` records the reason the provider gave.

Two limits worth knowing before you commit:

- **No shell access.** Import `database/schema.sql` through phpMyAdmin rather
  than running the migration script, and skip `scripts/doctor.php` unless the
  host offers a way to run a PHP file from the browser.
- **`.docx` import needs `zip`.** If the extension is missing, PDF, plain text,
  and pasted text import still work and the app says so.

Docker is not involved on a plan like this: you upload files over FTP into
`htdocs/` and Apache and PHP are already configured. A container only becomes
relevant on a VPS or a platform such as Render, Railway, or Fly.

### Two things to confirm before you buy

- **The document root can be moved to `public/`.** Without it the app runs, but
  every URL carries `/public`, and `app/`, `config/`, and `storage/` sit inside
  the web root protected only by the root `.htaccess`.
- **`.htaccess` is honoured.** Clean URLs depend on mod_rewrite. If the host
  ignores `.htaccess`, every route except the home page returns 404.

## Operations

- Rotate and retain application and mail logs.
- Monitor PHP and database errors.
- Back up daily or more often according to usage.
- Test a restoration before announcing general availability.
- Add rate limiting at the reverse proxy for login and reset routes in addition
  to the application-level limiter.
- Review Content Security Policy requirements before adding external analytics
  or AI providers.
