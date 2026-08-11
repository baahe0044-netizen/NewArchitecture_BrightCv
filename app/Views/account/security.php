<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Security · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('account/account.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body>
<?php View::partial('components/app_header', compact('user')); ?>
<main class="page-shell">
    <div class="container account-container">
        <section class="page-heading">
            <div><p class="eyebrow">Account</p><h1>Security</h1><p>Use a unique password and sign out of any device you no longer control.</p></div>
        </section>
        <div class="account-grid">
            <?php View::partial('components/account_nav', ['active' => 'security']); ?>
            <section class="card account-card">
                <?php View::partial('components/flash', compact('message', 'error')); ?>
                <div class="account-card-heading"><div class="large-avatar security-avatar">⌁</div><div><h2>Change password</h2><p>Your new password must be different from passwords used on other sites.</p></div></div>
                <form class="account-form" method="post" action="<?= e(base_url('/account/password')) ?>">
                    <?= Csrf::field() ?>
                    <div class="field"><label for="current_password">Current password</label><div class="password-wrap"><input id="current_password" type="password" name="current_password" autocomplete="current-password" required><button type="button" data-password-toggle="current_password">Show</button></div></div>
                    <div class="field"><label for="password">New password</label><div class="password-wrap"><input id="password" type="password" name="password" autocomplete="new-password" required><button type="button" data-password-toggle="password">Show</button></div><span class="field-hint">At least 8 characters with uppercase, lowercase, and a number.</span></div>
                    <div class="field"><label for="password_confirmation">Confirm new password</label><div class="password-wrap"><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation">Show</button></div></div>
                    <div class="account-form-actions"><button class="btn btn-primary" type="submit">Change password</button></div>
                </form>
                <div class="security-note"><span>✓</span><div><b>Your session is protected</b><p>Secure, HTTP-only cookies and session ID rotation are enabled. Signing out clears the active session.</p></div></div>
            </section>
        </div>
    </div>
</main>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
