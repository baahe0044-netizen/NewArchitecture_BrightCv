<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="page-shell">
    <div class="container">
        <section class="card empty-state" style="max-width:620px;margin:10vh auto 0">
            <div class="empty-state-icon">404</div>
            <h1>That page is not here</h1>
            <p>The link may be outdated, or the CV may no longer exist.</p>
            <a class="btn btn-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/')) ?>">Return to safety</a>
        </section>
    </div>
</main>
</body>
</html>
