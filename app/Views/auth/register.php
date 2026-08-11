<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Create account · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-visual register-visual">
        <a class="auth-brand" href="<?= e(base_url('/')) ?>"><?php View::partial('components/logo'); ?></a>
        <div class="auth-visual-copy">
            <span class="auth-kicker">Your story, clearly told</span>
            <h1>Turn your experience into opportunity.</h1>
            <p>Professional layouts, practical writing help, job matching, and ATS guidance from the first draft.</p>
        </div>
        <ul class="auth-benefits">
            <li><i>✓</i><div><b>Build at your pace</b><span>Secure autosave keeps every useful change.</span></div></li>
            <li><i>✓</i><div><b>Tailor with evidence</b><span>Match keywords without copying a job description.</span></div></li>
            <li><i>✓</i><div><b>Export with confidence</b><span>Clean A4 layouts stay readable on screen and paper.</span></div></li>
        </ul>
    </section>

    <section class="auth-form-side">
        <div class="auth-mobile-brand"><?php View::partial('components/logo'); ?></div>
        <div class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">Start building</p>
                <h2>Create your free account</h2>
                <p>Your first professional CV is a few steps away.</p>
            </div>

            <form class="auth-form" method="post" action="<?= e(base_url('/register')) ?>">
                <?= Csrf::field() ?>
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" placeholder="Your full name" required autofocus>
                    <?php foreach ($errors['name'] ?? [] as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?>
                </div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" placeholder="you@example.com" required>
                    <?php foreach ($errors['email'] ?? [] as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input id="password" type="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" required>
                        <button type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                    <span class="field-hint">Use uppercase, lowercase, and a number.</span>
                    <?php foreach ($errors['password'] ?? [] as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-wrap">
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat your password" required>
                        <button type="button" data-password-toggle="password_confirmation" aria-label="Show password">Show</button>
                    </div>
                    <?php foreach ($errors['password_confirmation'] ?? [] as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?>
                </div>
                <button class="btn btn-primary auth-submit" type="submit">Create account</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="<?= e(base_url('/login')) ?>">Sign in</a></p>
        </div>
        <p class="auth-legal">Your CV data remains private to your account. You control what you export and share.</p>
    </section>
</main>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
