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
            <div><p class="eyebrow">Account</p><h1>Appearance</h1><p>Choose how BrightCV looks on this device. This does not change how your CVs print or export — those always stay print-ready and light.</p></div>
        </section>
        <div class="account-grid">
            <?php View::partial('components/account_nav', ['active' => 'appearance']); ?>
            <section class="card account-card">
                <div class="account-card-heading"><div class="large-avatar">◐</div><div><h2>Theme</h2><p>System follows your device automatically; Light and Dark stay fixed regardless of your device setting.</p></div></div>

                <div class="theme-options" role="radiogroup" aria-label="Theme">
                    <label class="theme-option">
                        <input type="radio" name="theme" value="system">
                        <span class="theme-option-card">
                            <span class="theme-swatch theme-swatch-system"><span></span><span></span></span>
                            <span class="theme-option-label"><b>System</b><span class="theme-option-check">✓</span></span>
                            <p>Matches your device setting automatically.</p>
                        </span>
                    </label>
                    <label class="theme-option">
                        <input type="radio" name="theme" value="light">
                        <span class="theme-option-card">
                            <span class="theme-swatch theme-swatch-light"><span></span><span></span></span>
                            <span class="theme-option-label"><b>Light</b><span class="theme-option-check">✓</span></span>
                            <p>Always light, regardless of device setting.</p>
                        </span>
                    </label>
                    <label class="theme-option">
                        <input type="radio" name="theme" value="dark">
                        <span class="theme-option-card">
                            <span class="theme-swatch theme-swatch-dark"><span></span><span></span></span>
                            <span class="theme-option-label"><b>Dark</b><span class="theme-option-check">✓</span></span>
                            <p>Always dark, regardless of device setting.</p>
                        </span>
                    </label>
                </div>
            </section>
        </div>
    </div>
</main>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
<script src="<?= e(asset('account/appearance.js')) ?>" defer></script>
</body>
</html>
