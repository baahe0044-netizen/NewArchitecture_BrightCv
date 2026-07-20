<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a new password · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body class="auth-page simple-auth-page">
<main class="simple-auth">
    <div class="auth-mobile-brand"><?php View::partial('components/logo'); ?></div>
    <section class="auth-card">
        <a class="back-link" href="<?= e(base_url('/login')) ?>">← Back to sign in</a>
        <div class="reset-icon">⌁</div>
        <div class="auth-heading">
            <p class="eyebrow">Secure reset</p>
            <h2>Choose a new password</h2>
            <p>Use a strong password that you do not use for another account.</p>
        </div>
        <?php View::partial('components/flash', ['error' => $error ?? null]); ?>
        <form class="auth-form" method="post" action="<?= e(base_url('/reset-password')) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="hidden" name="email" value="<?= e($email) ?>">
            <div class="field"><label for="password">New password</label><div class="password-wrap"><input id="password" type="password" name="password" autocomplete="new-password" required><button type="button" data-password-toggle="password">Show</button></div><span class="field-hint">At least 8 characters with uppercase, lowercase, and a number.</span></div>
            <div class="field"><label for="password_confirmation">Confirm password</label><div class="password-wrap"><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation">Show</button></div></div>
            <button class="btn btn-primary auth-submit" type="submit">Reset password</button>
        </form>
    </section>
</main>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
