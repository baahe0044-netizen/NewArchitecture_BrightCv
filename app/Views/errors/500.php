<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Something went wrong · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
</head>
<body>
<main class="page-shell">
    <div class="container">
        <section class="card empty-state" style="max-width:620px;margin:10vh auto 0">
            <div class="empty-state-icon">!</div>
            <h1>We hit an unexpected problem</h1>
            <p>Your data is still safe. Refresh the page or return to your dashboard and try again.</p>
            <a class="btn btn-primary" href="<?= e(base_url('/dashboard')) ?>">Open dashboard</a>
        </section>
    </div>
</main>
</body>
</html>
