<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <title>Reset password · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body class="auth-page simple-auth-page">
<main class="simple-auth">
    <div class="auth-mobile-brand"><?php View::partial('components/logo'); ?></div>
    <section class="auth-card" aria-labelledby="reset-request-title">
        <a class="back-link" href="<?= e(base_url('/login')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            Back to sign in
        </a>
        <div class="reset-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
        </div>
        <div class="auth-heading">
            <p class="eyebrow">Account recovery</p>
            <h1 id="reset-request-title">Reset your password</h1>
            <p>Enter your account email. If it matches an account, we will send secure reset instructions.</p>
        </div>
        <?php View::partial('components/flash', ['message' => $message ?? null]); ?>
        <form class="auth-form" method="post" action="<?= e(base_url('/forgot-password')) ?>">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required autofocus>
            </div>
            <button class="btn btn-primary auth-submit" type="submit">Send reset instructions</button>
        </form>
    </section>
</main>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
</body>
</html>
