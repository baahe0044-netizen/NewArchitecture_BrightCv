<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Sign in · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('auth/auth.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-visual">
        <a class="auth-brand" href="<?= e(base_url('/')) ?>"><?php View::partial('components/logo'); ?></a>
        <div class="auth-visual-copy">
            <span class="auth-kicker">Build with confidence</span>
            <h1>A sharper CV is waiting for you.</h1>
            <p>Continue building, tailor your application, and check every detail before you apply.</p>
        </div>
        <div class="auth-preview">
            <div class="auth-preview-paper">
                <div class="preview-top"><span>EB</span><div><b>EMMANUEL BAAH</b><small>SOFTWARE ENGINEER</small></div></div>
                <hr><b>PROFILE</b><i></i><i></i><b>EXPERIENCE</b><i></i><i></i><i></i><b>SKILLS</b>
                <div class="preview-pills"><span>PHP</span><span>Python</span><span>React</span></div>
            </div>
            <div class="auth-score"><strong>88</strong><span>ATS ready</span></div>
        </div>
        <p class="auth-quote">“Make every line earn its place.”</p>
    </section>

    <section class="auth-form-side">
        <div class="auth-mobile-brand"><?php View::partial('components/logo'); ?></div>
        <div class="auth-card">
            <div class="auth-heading">
                <p class="eyebrow">Welcome back</p>
                <h2>Sign in to your account</h2>
                <p>Pick up exactly where you left off.</p>
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
                        <input id="password" type="password" name="password" autocomplete="current-password" placeholder="Your password" required>
                        <button type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                </div>
                <button class="btn btn-primary auth-submit" type="submit">Sign in</button>
            </form>

            <p class="auth-switch">New to LunettiStar? <a href="<?= e(base_url('/register')) ?>">Create a free account</a></p>
        </div>
        <p class="auth-legal">By continuing, you agree to use LunettiStar responsibly and keep your account secure.</p>
    </section>
</main>
<script src="<?= e(asset('auth/auth.js')) ?>" defer></script>
</body>
</html>
