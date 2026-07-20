<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="error-page">
    <div class="error-wrap">
        <div class="error-brand"><?php View::partial('components/logo'); ?></div>
        <section class="card empty-state error-card">
            <span class="error-code">Error 404</span>
            <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="25" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M8.5 11h5"/></svg></div>
            <h1>We could not find that page</h1>
            <p>The link may be outdated, or the CV may no longer exist.</p>
            <a class="btn btn-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/')) ?>"><?= Auth::check() ? 'Go to dashboard' : 'Go to home page' ?></a>
        </section>
    </div>
</main>
</body>
</html>
