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

## Operations

- Rotate and retain application and mail logs.
- Monitor PHP and database errors.
- Back up daily or more often according to usage.
- Test a restoration before announcing general availability.
- Add rate limiting at the reverse proxy for login and reset routes in addition
  to the application-level limiter.
- Review Content Security Policy requirements before adding external analytics
  or AI providers.
