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
            <div><p class="eyebrow">Account</p><h1>Security</h1><p>Use a unique password and sign out when you finish using a shared device.</p></div>
        </section>
        <div class="account-grid">
            <?php View::partial('components/account_nav', ['active' => 'security']); ?>
            <section class="card account-card" aria-labelledby="password-form-title">
                <?php View::partial('components/flash', compact('message', 'error')); ?>
                <div class="account-card-heading">
                    <div class="large-avatar security-avatar" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/></svg>
                    </div>
                    <div><h2 id="password-form-title">Change password</h2><p>Your new password should be different from passwords used on other sites.</p></div>
                </div>
                <form class="account-form" method="post" action="<?= e(base_url('/account/password')) ?>">
                    <?= Csrf::field() ?>
                    <div class="field">
                        <label for="current_password">Current password</label>
                        <div class="password-wrap"><input id="current_password" type="password" name="current_password" autocomplete="current-password" required><button type="button" data-password-toggle="current_password" aria-controls="current_password" aria-pressed="false" aria-label="Show password">Show</button></div>
                    </div>
                    <div class="field">
                        <label for="password">New password</label>
                        <div class="password-wrap"><input id="password" type="password" name="password" autocomplete="new-password" aria-describedby="password-requirements" required><button type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false" aria-label="Show password">Show</button></div>
                        <span class="field-hint" id="password-requirements">At least 8 characters with uppercase, lowercase, and a number.</span>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">Confirm new password</label>
                        <div class="password-wrap"><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required><button type="button" data-password-toggle="password_confirmation" aria-controls="password_confirmation" aria-pressed="false" aria-label="Show password">Show</button></div>
                    </div>
                    <div class="account-form-actions"><button class="btn btn-primary" type="submit">Change password</button></div>
                </form>
                <div class="security-note">
                    <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></span>
                    <div><b>Session protection is active</b><p>Sessions use HTTP-only, same-site cookies. The session ID is renewed after a password change, and signing out clears it.</p></div>
                </div>
            </section>
        </div>
    </div>
</main>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
