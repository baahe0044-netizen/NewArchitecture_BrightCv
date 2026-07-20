# Installation

## WAMP on Windows

1. Confirm WAMP is green and select PHP 8.1 or newer.
2. Copy the project into C:\wamp64\www.
3. Copy .env.example to .env.
4. Set APP_URL to the folder’s public URL.
5. Set APP_KEY to a long random value, then set DB_DATABASE, DB_USERNAME, and
   DB_PASSWORD. Generate a key with:

       php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
6. Open a terminal in the project and run:

       C:\wamp64\bin\php\php8.2.0\php.exe database\migrate.php

   Adjust php8.2.0 if a different WAMP PHP version is selected.
7. Ensure Apache mod_rewrite is enabled.
8. Open the APP_URL value in a browser and create an account.

The application has a fallback autoloader. If Composer is available, this
optional command creates an optimized production class map:

    composer dump-autoload -o

## phpMyAdmin alternative

Select Import, choose database/schema.sql, and execute it. Then confirm that the
database name matches DB_DATABASE in .env.

## Common problems

### Page not found for every route

Enable Apache mod_rewrite and AllowOverride All for the WAMP www directory, then
restart Apache.

### Database connection error

Confirm MySQL is running, the database exists, and the .env credentials are
correct. Do not add quotation marks unless they are part of the password.

### Assets load from the wrong folder

APP_URL must exactly match the browser URL through the public directory. Do not
include a trailing slash.

### Password reset email does not arrive

Local development uses MAIL_DRIVER=log. The reset message and secure link are
written to storage/logs/mail.log. Configure MAIL_DRIVER=mail and the server’s
mail transport for production.
