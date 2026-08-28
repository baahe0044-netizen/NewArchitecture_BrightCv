# Error messages and troubleshooting

This website no longer shows raw PHP errors, SQL messages or blank white
pages. Every failure is caught, written to a log file, and presented as a
branded page (or an inline message) that explains what happened and what to
do about it.

## Where errors are recorded

Technical detail is written to:

```
storage/logs/app-YYYY-MM.log
```

A new log file is started each month. The folder is blocked from the web by
`storage/.htaccess`, so visitors cannot read it.

Each serious error is given a short **reference code** (for example
`A1B2C3D4`). The code is shown to the person who hit the error and written
into the log, so a report of "I saw reference A1B2C3D4" can be matched to the
exact entry.

## Messages you may see, and what they mean

| Message | Cause | Fix |
|---|---|---|
| The website cannot reach the database server right now. | MySQL is not running, or the host/port is wrong. | Start MySQL in WAMP/XAMPP; check `config/database.php`. |
| The database refused the username or password the website is using. | Wrong database user, wrong password, or that user has no rights on the database. | Correct the details in `config/database.php` or `config/config.local.php`. |
| The website database has not been created yet. | The database named in the config does not exist. | Import `database.sql` in phpMyAdmin. |
| The website setup is not finished — N tables are missing. | `database.sql` was only partly imported. | Import `database.sql` again. |
| Your session timed out. | The form sat open until its security token expired. | Reopen the form and submit it again. |
| You are signed out. | An admin form was submitted after the session ended. | Sign in again and resubmit. |
| Something went wrong. (with a reference code) | An unexpected fault. | Look the reference code up in `storage/logs`. |

## Turning on technical detail (developers only)

By default the technical detail is only written to the log. To also show it on
screen while developing, create an empty file:

```
config/debug.flag
```

Delete that file before putting the website live — with it present, visitors
can see file paths and stack traces.

You can also set the environment variable `APP_DEBUG=1` instead.

## Database settings

`config/database.php` holds the defaults. To use different credentials without
editing that file (recommended on a live server), copy
`config/config.sample.php` to `config/config.local.php` and edit the copy.
`config.local.php` is ignored by git and blocked from the web, so live
passwords are never committed or served.

The environment variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and
`DB_PASS` override both files if they are set.
