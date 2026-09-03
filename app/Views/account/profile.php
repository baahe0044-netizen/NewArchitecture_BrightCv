<?php
$nameErrors = $errors['name'] ?? [];
$jobTitleErrors = $errors['job_title'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Profile settings · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('account/account.css')) ?>">
</head>
<body>
<?php View::partial('components/app_header', compact('user')); ?>
<main class="page-shell">
    <div class="container account-container">
        <section class="page-heading">
            <div><p class="eyebrow">Account</p><h1>Profile settings</h1><p>Manage the account details and defaults used in your CV workspace.</p></div>
        </section>
        <div class="account-grid">
            <?php View::partial('components/account_nav', ['active' => 'profile']); ?>
            <section class="card account-card" aria-labelledby="profile-form-title">
                <?php View::partial('components/flash', compact('message', 'error')); ?>
                <div class="account-card-heading">
                    <div class="large-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $user['name'], 0, 1))) ?></div>
                    <div><h2 id="profile-form-title">Personal information</h2><p>These details belong to your account and are not automatically added to a CV.</p></div>
                </div>
                <form class="account-form" method="post" action="<?= e(base_url('/account/profile')) ?>">
                    <?= Csrf::field() ?>
                    <div class="field">
                        <label for="name">Full name</label>
                        <input id="name" name="name" value="<?= e($user['name']) ?>" maxlength="100" autocomplete="name" required<?= $nameErrors ? ' aria-invalid="true" aria-describedby="name-errors"' : '' ?>>
                        <?php if ($nameErrors): ?><div class="form-errors" id="name-errors" role="alert"><?php foreach ($nameErrors as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" value="<?= e($user['email']) ?>" disabled aria-describedby="email-hint">
                        <span class="field-hint" id="email-hint">Email changes require a separate verification workflow.</span>
                    </div>
                    <div class="field">
                        <label for="job_title">Current or target role</label>
                        <input id="job_title" name="job_title" value="<?= e($user['job_title'] ?? '') ?>" maxlength="120" placeholder="e.g. Software Engineer"<?= $jobTitleErrors ? ' aria-invalid="true" aria-describedby="job-title-errors"' : '' ?>>
                        <?php if ($jobTitleErrors): ?><div class="form-errors" id="job-title-errors" role="alert"><?php foreach ($jobTitleErrors as $formError): ?><span class="form-error"><?= e($formError) ?></span><?php endforeach; ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="locale">Default CV language</label>
                        <select id="locale" name="locale">
                            <option value="en" <?= ($user['locale'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                            <option value="fr" <?= ($user['locale'] ?? '') === 'fr' ? 'selected' : '' ?>>Français</option>
                            <option value="es" <?= ($user['locale'] ?? '') === 'es' ? 'selected' : '' ?>>Español</option>
                        </select>
                    </div>
                    <div class="account-form-actions"><button class="btn btn-primary" type="submit">Save profile</button></div>
                </form>
            </section>
        </div>
    </div>
</main>
<?php View::partial('components/bottom_nav', ['active' => 'account']); ?>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
</body>
</html>
