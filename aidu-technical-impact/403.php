<?php
declare(strict_types=1);
require __DIR__ . '/config/errors.php';
app_render_error_page(
    403,
    'That area is private',
    'You do not have permission to open this part of the website.',
    [
        'If you are the administrator, sign in from the admin login page.',
        'Otherwise, use the menu on the homepage to find what you need.',
    ]
);
