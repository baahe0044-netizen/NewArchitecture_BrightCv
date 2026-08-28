<?php
declare(strict_types=1);
require __DIR__ . '/config/errors.php';
app_render_error_page(
    400,
    'That request could not be read',
    'The address or the information sent with it was not something this website understands.',
    [
        'Check the address for a typing mistake.',
        'Go back and submit the form again.',
    ]
);
