<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

if (admin_logged_in()) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$error = '';
$notice = '';

// If the database is unreachable, say so here rather than letting the login
// query blow up into a fatal error page.
if (!db_available()) {
    $notice = 'The website cannot reach the database right now. Start MySQL, confirm the details in config/database.php, then reload this page.';
} elseif (($missingTables = db_missing_tables()) !== []) {
    $notice = 'The database is missing these tables: ' . implode(', ', $missingTables)
        . '. Import database.sql through phpMyAdmin, then reload this page.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        if (login_attempts_exceeded()) {
            throw new UserMessageException('Too many failed sign-in attempts. Please wait 15 minutes before trying again.');
        }

        $username = post_text('username');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            throw new UserMessageException('Enter both your username and your password.');
        }

        $user = db_row('SELECT * FROM users WHERE username=? LIMIT 1', [$username]);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            record_login_failure();
            // Deliberately the same wording for both cases so the form does not
            // reveal which usernames exist.
            throw new UserMessageException('Incorrect username or password. Check your details and try again.');
        }

        clear_login_failures();
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];

        $redirect = (string) ($_SESSION['admin_redirect'] ?? '');
        unset($_SESSION['admin_redirect']);
        header('Location: ' . ($redirect !== '' && str_starts_with($redirect, '/') ? $redirect : url('admin/index.php')));
        exit;
    } catch (Throwable $e) {
        $error = inline_exception_message($e, 'Sign-in could not be completed just now. Please try again in a moment.');
    }
}

$adminsRegistered = db_available() ? admin_count() : -1;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login &middot; AID-U Technical Impact</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
<div class="login-box">
    <div class="login-brand">
        <img class="login-logo" src="<?= e(url('assets/images/aid-u-technical-impact-logo.jpg')) ?>" alt="AID-U Technical Impact">
        <span><strong>AID-U TECHNICAL IMPACT</strong><span>ADMIN CONTROL CENTRE</span></span>
    </div>
    <h1>Administrator Login</h1>
    <p>Securely manage projects, services, enquiries and company information.</p>

    <?php if ($notice !== ''): ?>
        <div class="admin-alert error" role="alert"><i class="fa-solid fa-triangle-exclamation"></i> <?= e($notice) ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <div class="admin-alert error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field"><label for="username">Username</label><input id="username" name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
        <button class="admin-button" type="submit">Sign In <i class="fa-solid fa-arrow-right"></i></button>
    </form>

    <?php if ($adminsRegistered === 0): ?>
        <p class="register-note">No administrator exists yet. <a href="<?= e(url('admin/register.php')) ?>">Register the first administrator</a>.</p>
    <?php endif; ?>
</div>
</body>
</html>
