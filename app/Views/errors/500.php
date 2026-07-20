<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Something went wrong · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="error-page">
    <div class="error-wrap">
        <div class="error-brand"><?php View::partial('components/logo'); ?></div>
        <section class="card empty-state error-card">
            <span class="error-code">Server error</span>
            <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="25" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 17.5v.2"/></svg></div>
            <h1>Something went wrong</h1>
            <p>The request could not be completed. Wait a moment, then return and try again.</p>
            <a class="btn btn-primary" href="<?= e(base_url('/')) ?>">Go to home page</a>
        </section>
    </div>
</main>
</body>
</html>
