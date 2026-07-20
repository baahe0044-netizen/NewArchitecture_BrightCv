<?php
$currentPath = Request::capture()->path();
$headerUser = $user ?? Auth::user();
?>
<header class="app-header">
    <div class="app-header-inner">
        <?php View::partial('components/logo'); ?>

        <nav class="app-nav" aria-label="Main navigation">
            <a class="<?= str_starts_with($currentPath, '/dashboard') ? 'active' : '' ?>" href="<?= e(base_url('/dashboard')) ?>">Dashboard</a>
            <a class="<?= str_starts_with($currentPath, '/templates') ? 'active' : '' ?>" href="<?= e(base_url('/templates')) ?>">Templates</a>
            <a href="<?= e(base_url('/account/profile')) ?>">Account</a>
        </nav>

        <div class="header-actions">
            <?php if ($headerUser): ?>
                <a class="user-chip" href="<?= e(base_url('/account/profile')) ?>">
                    <span class="user-avatar"><?= e(mb_strtoupper(mb_substr((string) ($headerUser['name'] ?? 'U'), 0, 1))) ?></span>
                    <span><?= e($headerUser['name'] ?? 'Account') ?></span>
                </a>
                <form method="post" action="<?= e(base_url('/logout')) ?>">
                    <?= Csrf::field() ?>
                    <button class="icon-btn" type="submit" title="Sign out" aria-label="Sign out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </button>
                </form>
            <?php endif; ?>
            <button class="icon-btn mobile-menu-button" type="button" data-mobile-menu aria-expanded="false" aria-label="Toggle menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
</header>
