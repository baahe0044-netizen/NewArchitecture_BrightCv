<?php
$nameErrors = $errors['name'] ?? [];
$emailErrors = $errors['email'] ?? [];
$passwordErrors = $errors['password'] ?? [];
$confirmationErrors = $errors['password_confirmation'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Create account · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-visual register-visual" aria-labelledby="register-context-title">
        <div class="auth-brand"><?php View::partial('components/logo'); ?></div>
        <div class="auth-visual-copy">
            <p class="auth-kicker">A clear place to start</p>
            <h1 id="register-context-title">Build your CV one useful section at a time.</h1>
            <p>Choose a layout, add your experience, review practical feedback, and keep improving each application.</p>
        </div>
        <ul class="auth-benefits">
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                <div><b>Your work is saved</b><span>Continue from the latest version whenever you return.</span></div>
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                <div><b>Feedback stays practical</b><span>Improve structure, wording, and relevance before applying.</span></div>
            </li>
            <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                <div><b>Templates stay flexible</b><span>Change the design without re-entering your CV content.</span></div>
            </li>
        </ul>
    </section>

    <section class="auth-form-side">
        <div class="auth-mobile-brand"><?php View::partial('components/logo'); ?></div>
        <div class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">Get started</p>
                <h2>Create your account</h2>
                <p>Use an email address you can access if you need to reset your password.</p>
            </div>

            <form class="auth-form" method="post" action="<?= e(base_url('/register')) ?>">
                <?= Csrf::field() ?>
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" value="<?= e($old['name'] ?? '') ?>" autocomplete="name" placeholder="Your full name" required autofocus<?= $nameErrors ? ' aria-invalid="true" aria-describedby="name-errors"' : '' ?>>
                    <?php if ($nameErrors): ?><div class="form-errors" id="name-errors" role="alert"><?php foreach ($nameErrors as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" placeholder="you@example.com" required<?= $emailErrors ? ' aria-invalid="true" aria-describedby="email-errors"' : '' ?>>
                    <?php if ($emailErrors): ?><div class="form-errors" id="email-errors" role="alert"><?php foreach ($emailErrors as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input id="password" type="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" required aria-describedby="password-hint<?= $passwordErrors ? ' password-errors' : '' ?>"<?= $passwordErrors ? ' aria-invalid="true"' : '' ?>>
                        <button type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false" aria-label="Show password">Show</button>
                    </div>
                    <span class="field-hint" id="password-hint">Use uppercase, lowercase, and a number.</span>
                    <?php if ($passwordErrors): ?><div class="form-errors" id="password-errors" role="alert"><?php foreach ($passwordErrors as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-wrap">
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat your password" required<?= $confirmationErrors ? ' aria-invalid="true" aria-describedby="confirmation-errors"' : '' ?>>
                        <button type="button" data-password-toggle="password_confirmation" aria-controls="password_confirmation" aria-pressed="false" aria-label="Show password">Show</button>
                    </div>
                    <?php if ($confirmationErrors): ?><div class="form-errors" id="confirmation-errors" role="alert"><?php foreach ($confirmationErrors as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?></div><?php endif; ?>
                </div>
                <button class="btn btn-primary auth-submit" type="submit">Create account</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="<?= e(base_url('/login')) ?>">Sign in</a></p>
        </div>
        <p class="auth-legal">Your CV remains private to your account until you choose to export it.</p>
    </section>
</main>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
