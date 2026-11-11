# Contributing

Create a focused branch from main and keep changes inside the existing
controller, service, repository, and view boundaries.

Before opening a pull request:

    npm install
    npm run check

When PHP is installed locally, also run:

    php tests/run.php

Do not commit .env, generated PDFs, uploads, logs, database dumps containing
personal data, or node_modules. New API writes must include both authentication
and CSRF middleware. New CV queries must enforce authenticated ownership.
