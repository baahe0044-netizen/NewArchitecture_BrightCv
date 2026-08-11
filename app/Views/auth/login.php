<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Sign in · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-visual" aria-labelledby="auth-context-title">
        <div class="auth-brand"><?php View::partial('components/logo'); ?></div>
        <div class="auth-visual-copy">
            <p class="auth-kicker">Your CV workspace</p>
            <h1 id="auth-context-title">Continue from your latest draft.</h1>
            <p>Review your content, tailor it for the role, and export the finished CV when it is ready.</p>
        </div>
        <div class="auth-workspace" aria-hidden="true">
            <div class="auth-workspace-head">
                <div><span>Marketing Manager CV</span><small>Updated recently</small></div>
                <strong><i></i> Saved</strong>
            </div>
            <div class="auth-workspace-body">
                <div class="auth-document-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 15h6M9 18h4"/></svg>
                </div>
                <div><b>Ready to edit</b><span>Personal details · Experience · Education · Skills</span></div>
            </div>
            <div class="auth-workspace-footer"><span>Completion</span><div><i></i></div><b>82%</b></div>
        </div>
    </section>

    <section class="auth-form-side">
        <div class="auth-mobile-brand"><?php View::partial('components/logo'); ?></div>
        <div class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">Welcome back</p>
                <h2>Sign in to your account</h2>
                <p>Enter the email and password you used to register.</p>
            </div>

            <?php View::partial('components/flash', compact('message', 'error')); ?>

            <form class="auth-form" method="post" action="<?= e(base_url('/login')) ?>">
                <?= Csrf::field() ?>
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="<?= e($oldEmail ?? '') ?>" autocomplete="email" placeholder="you@example.com" required autofocus>
                </div>
                <div class="field">
                    <div class="label-row"><label for="password">Password</label><a href="<?= e(base_url('/forgot-password')) ?>">Forgot password?</a></div>
                    <div class="password-wrap">
                        <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                        <button type="button" data-password-toggle="password" aria-controls="password" aria-pressed="false" aria-label="Show password">Show</button>
                    </div>
                </div>
                <button class="btn btn-primary auth-submit" type="submit">Sign in</button>
            </form>

            <p class="auth-switch">New to <?= e(APP_NAME) ?>? <a href="<?= e(base_url('/register')) ?>">Create an account</a></p>
        </div>
        <p class="auth-legal">Keep your sign-in details private, especially on shared devices.</p>
    </section>
</main>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
