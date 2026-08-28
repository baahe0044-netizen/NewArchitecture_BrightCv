<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

if (!db_available()) {
    app_render_error_page(
        503,
        'The database is not ready yet',
        'An administrator account cannot be created until the website can reach its database.',
        [
            'Start MySQL in WAMP / XAMPP.',
            'Import database.sql through phpMyAdmin to create the "aidutech" database.',
            'Reload this page once both are done.',
        ]
    );
    exit;
}

// Registration closes as soon as one administrator exists.
if (admin_count() > 0) {
    header('Location: ' . url('admin/login.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $name = post_text('full_name');
        $username = post_text('username');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($name === '') {
            throw new UserMessageException('Please enter your full name.');
        }
        if ($username === '' || strlen($username) < 4) {
            throw new UserMessageException('Choose a username of at least 4 characters.');
        }
        if (strlen($password) < 10) {
            throw new UserMessageException('Your password must be at least 10 characters long.');
        }
        if ($password !== $confirm) {
            throw new UserMessageException('The two passwords do not match. Please type them again.');
        }
        // Re-checked inside the request in case two people opened this page at once.
        if (admin_count() > 0) {
            throw new UserMessageException('An administrator already exists, so registration is closed. Use the login page instead.');
        }

        $stmt = db()->prepare('INSERT INTO users(username,password_hash,full_name) VALUES(?,?,?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name]);

        flash('success', 'Administrator created. You can now sign in.');
        header('Location: ' . url('admin/login.php'));
        exit;
    } catch (PDOException $e) {
        // A duplicate username is a person-level problem, not a system failure.
        if (($e->errorInfo[1] ?? 0) === 1062) {
            $error = 'That username is already taken. Please choose another one.';
        } else {
            $error = inline_exception_message($e, 'The administrator account could not be created just now.');
        }
    } catch (Throwable $e) {
        $error = inline_exception_message($e, 'The administrator account could not be created just now.');
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register Administrator &middot; AID-U Technical Impact</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
<div class="login-box">
    <div class="login-brand">
        <img class="login-logo" src="<?= e(url('assets/images/aid-u-technical-impact-logo.jpg')) ?>" alt="AID-U Technical Impact">
        <span><strong>AID-U TECHNICAL IMPACT</strong><span>ADMIN CONTROL CENTRE</span></span>
    </div>
    <h1>Create Administrator</h1>
    <p>Create the first administrator account for the AID-U Technical Impact control centre.</p>

    <?php if ($error !== ''): ?>
        <div class="admin-alert error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
        <div class="field"><label for="username">Username</label><input id="username" name="username" required minlength="4" value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" minlength="10" required autocomplete="new-password"><small>At least 10 characters.</small></div>
        <div class="field"><label for="confirm_password">Confirm password</label><input id="confirm_password" type="password" name="confirm_password" minlength="10" required autocomplete="new-password"></div>
        <button class="admin-button" type="submit">Create Administrator</button>
    </form>
</div>
</body>
</html>
