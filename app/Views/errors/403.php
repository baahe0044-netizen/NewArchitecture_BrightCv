<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="page-shell">
    <div class="container">
        <section class="card empty-state" style="max-width:620px;margin:10vh auto 0">
            <div class="empty-state-icon">403</div>
            <h1>You cannot open this page</h1>
            <p>Your account does not have permission to access this resource.</p>
            <a class="btn btn-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/login')) ?>">Return to safety</a>
        </section>
    </div>
</main>
</body>
</html>
