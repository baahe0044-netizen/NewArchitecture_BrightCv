<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="error-page">
    <div class="error-wrap">
        <div class="error-brand"><?php View::partial('components/logo'); ?></div>
        <section class="card empty-state error-card">
            <span class="error-code">Error 403</span>
            <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="25" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 7.5-2"/></svg></div>
            <h1>You do not have access to this page</h1>
            <p>Sign in with the correct account, or return to your dashboard.</p>
            <a class="btn btn-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/login')) ?>"><?= Auth::check() ? 'Go to dashboard' : 'Sign in' ?></a>
        </section>
    </div>
</main>
</body>
</html>
