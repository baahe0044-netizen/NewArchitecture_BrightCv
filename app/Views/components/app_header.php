<?php
$currentPath = Request::capture()->path();
$headerUser = $user ?? Auth::user();

/**
 * One place decides the active section, so every nav item highlights
 * consistently. Previously "Account" could never appear active because it
 * had no matching rule.
 */
$section = 'other';
foreach (['dashboard' => '/dashboard', 'templates' => '/templates', 'account' => '/account'] as $key => $prefix) {
    if (str_starts_with($currentPath, $prefix)) {
        $section = $key;
        break;
    }
}

$navItems = [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/dashboard'],
    ['key' => 'templates', 'label' => 'Templates', 'href' => '/templates'],
    ['key' => 'account', 'label' => 'Account', 'href' => '/account/profile'],
];
?>
<header class="app-header">
    <div class="app-header-inner">
        <?php View::partial('components/logo'); ?>

        <nav class="app-nav" aria-label="Main navigation">
            <?php foreach ($navItems as $item): ?>
                <a
                    class="<?= $section === $item['key'] ? 'active' : '' ?>"
                    href="<?= e(base_url($item['href'])) ?>"
                    <?= $section === $item['key'] ? 'aria-current="page"' : '' ?>
                ><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <?php View::partial('components/theme_toggle'); ?>

            <?php if ($headerUser): ?>
                <a class="user-chip" href="<?= e(base_url('/account/profile')) ?>">
                    <span class="user-avatar"><?= e(mb_strtoupper(mb_substr((string) ($headerUser['name'] ?? 'U'), 0, 1))) ?></span>
                    <span><?= e($headerUser['name'] ?? 'Account') ?></span>
                </a>
                <form method="post" action="<?= e(base_url('/logout')) ?>">
                    <?= Csrf::field() ?>
                    <button class="icon-btn" type="submit" title="Sign out" aria-label="Sign out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M16 17l5-5-5-5M21 12H9"/></svg>
                    </button>
                </form>
            <?php endif; ?>

            <button class="icon-btn mobile-menu-button" type="button" data-mobile-menu aria-expanded="false" aria-label="Toggle menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
</header>
