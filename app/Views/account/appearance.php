<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Appearance · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('account/account.css')) ?>">
</head>
<body>
<?php View::partial('components/app_header', compact('user')); ?>
<main class="page-shell">
    <div class="container account-container">
        <section class="page-heading">
            <div><p class="eyebrow">Account</p><h1>Appearance</h1><p>BrightCV uses one look, warm paper with heavy ink edges. This does not change how your CVs print or export — those always stay print-ready and light.</p></div>
        </section>
        <?php View::partial('components/flash', ['message' => $message ?? null]); ?>
        <div class="account-grid">
            <?php View::partial('components/account_nav', ['active' => 'appearance']); ?>

            <section class="card account-card">
                <div class="account-card-heading"><div class="large-avatar">◑</div><div><h2>Levels, streaks, and badges</h2><p>The game layer across the dashboard, the builder, and Rewards.</p></div></div>

                <form method="post" action="<?= e(base_url('/account/gamification')) ?>" class="gamification-toggle-form">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="enabled" value="<?= $gamificationEnabled ? '0' : '1' ?>">
                    <div class="gamification-toggle-row">
                        <div>
                            <p class="gamification-toggle-state"><?= $gamificationEnabled ? 'On' : 'Off' ?></p>
                            <p class="gamification-toggle-hint">
                                <?php if ($gamificationEnabled): ?>
                                    XP, your level, streaks, and badges appear in the header, dashboard, and builder. Every number is worked out from your real CVs — nothing is awarded for logging in.
                                <?php else: ?>
                                    The dashboard and builder show plain numbers instead — "72% complete" rather than a level and a streak. Your CVs, their content, and your progress are unaffected either way.
                                <?php endif; ?>
                            </p>
                        </div>
                        <button class="toggle-switch" type="submit" role="switch" aria-checked="<?= $gamificationEnabled ? 'true' : 'false' ?>">
                            <span class="toggle-switch-track"><span class="toggle-switch-thumb"></span></span>
                            <span class="sr-only"><?= $gamificationEnabled ? 'Turn off levels, streaks, and badges' : 'Turn on levels, streaks, and badges' ?></span>
                        </button>
                    </div>
                </form>

                <?php if ($gamificationEnabled): ?>
                    <p class="field-hint">Read the full explanation on the <a href="<?= e(base_url('/rewards')) ?>">Rewards</a> page.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
<?php View::partial('components/bottom_nav', ['active' => 'account']); ?>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
</body>
</html>
