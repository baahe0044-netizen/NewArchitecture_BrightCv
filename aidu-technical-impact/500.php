<?php
declare(strict_types=1);
require __DIR__ . '/config/errors.php';
app_render_error_page(
    500,
    'The website hit a problem',
    'Something went wrong while building this page. The details have been recorded so it can be fixed.',
    [
        'Please reload the page in a moment.',
        'If it keeps happening, contact the website administrator.',
    ]
);
