<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

$_SESSION = [];

// Expire the session cookie as well, so the browser does not keep sending it.
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();
session_start();
session_regenerate_id(true);
flash('success', 'You have been signed out.');

header('Location: ' . url('admin/login.php'));
exit;
