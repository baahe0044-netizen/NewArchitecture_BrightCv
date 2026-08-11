<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session expired · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="error-page">
    <div class="error-wrap">
        <div class="error-brand"><?php View::partial('components/logo'); ?></div>
        <section class="card empty-state error-card">
            <span class="error-code">Session expired</span>
            <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="25" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 5v6h-6"/></svg></div>
            <h1>Please refresh and try again</h1>
            <p>The form was open for too long, so it was not submitted. Your session is protected from stale requests.</p>
            <a class="btn btn-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/login')) ?>"><?= Auth::check() ? 'Go to dashboard' : 'Sign in again' ?></a>
        </section>
    </div>
</main>
</body>
</html>
