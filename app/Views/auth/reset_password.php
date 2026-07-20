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
    <section class="auth-card" aria-labelledby="new-password-title">
        <a class="back-link" href="<?= e(base_url('/login')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Back to sign in
        </a>
        <div class="reset-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/></svg>
        </div>
        <div class="auth-heading">
            <p class="eyebrow">Secure reset</p>
            <h1 id="new-password-title">Choose a new password</h1>
            <p>Use a strong password that you do not use for another account.</p>
        </div>
        <?php View::partial('components/flash', ['error' => $error ?? null]); ?>
        <form class="auth-form" method="post" action="<?= e(base_url('/reset-password')) ?>">
            <?= Csrf::field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="hidden" name="email" value="<?= e($email) ?>">
            <div class="field">
                <label for="password">New password</label>
                <div class="password-wrap">
                    <input id="password" type="password" name="password" autocomplete="new-password" aria-describedby="new-password-hint" required>
                    <button type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false" aria-label="Show password">Show</button>
                </div>
                <span class="field-hint" id="new-password-hint">At least 8 characters with uppercase, lowercase, and a number.</span>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <div class="password-wrap">
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                    <button type="button" data-password-toggle="password_confirmation" aria-controls="password_confirmation" aria-pressed="false" aria-label="Show password">Show</button>
                </div>
            </div>
            <button class="btn btn-primary auth-submit" type="submit">Reset password</button>
        </form>
    </section>
</main>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
