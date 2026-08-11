<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body class="auth-page simple-auth-page">
<main class="simple-auth">
    <div class="auth-mobile-brand"><?php View::partial('components/logo'); ?></div>
    <section class="auth-card">
        <a class="back-link" href="<?= e(base_url('/login')) ?>">← Back to sign in</a>
        <div class="reset-icon">↗</div>
        <div class="auth-heading">
            <p class="eyebrow">Account recovery</p>
            <h2>Reset your password</h2>
            <p>Enter your account email. If it exists, we will send secure reset instructions.</p>
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
</body>
</html>
