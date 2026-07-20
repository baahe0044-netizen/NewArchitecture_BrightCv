<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session expired · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="page-shell">
    <div class="container">
        <section class="card empty-state" style="max-width:620px;margin:10vh auto 0">
            <div class="empty-state-icon">↻</div>
            <h1>Your session needs a refresh</h1>
            <p>This form was open for too long. Refresh the previous page and submit it again.</p>
            <a class="btn btn-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/login')) ?>">Continue securely</a>
        </section>
    </div>
</main>
</body>
</html>
