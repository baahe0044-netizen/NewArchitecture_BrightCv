<?php
$currentPath = Request::capture()->path();
$headerUser = $user ?? Auth::user();
$dashboardActive = str_starts_with($currentPath, '/dashboard');
$templatesActive = str_starts_with($currentPath, '/templates');
$rewardsActive = str_starts_with($currentPath, '/rewards');
$accountActive = str_starts_with($currentPath, '/account');

// Falls back to computing its own copy, the same way $headerUser falls back
// to Auth::user() -- but a view that already computed the full summary for
// its own body (the dashboard, for one) passes it in so this does not query
// twice for one page.
if (!isset($gamification)) {
    $gamification = (Auth::id() && GamificationService::isEnabled())
        ? (new GamificationService())->summaryForUser((int) Auth::id())
        : null;
}
?>
<header class="app-header">
    <div class="app-header-inner">
        <?php View::partial('components/logo'); ?>

        <nav class="app-nav" id="app-navigation" aria-label="Main navigation">
            <a class="<?= $dashboardActive ? 'active' : '' ?>" href="<?= e(base_url('/dashboard')) ?>"<?= $dashboardActive ? ' aria-current="page"' : '' ?>>Dashboard</a>
            <a class="<?= $templatesActive ? 'active' : '' ?>" href="<?= e(base_url('/templates')) ?>"<?= $templatesActive ? ' aria-current="page"' : '' ?>>Templates</a>
            <?php if ($gamification !== null): ?>
                <a class="<?= $rewardsActive ? 'active' : '' ?>" href="<?= e(base_url('/rewards')) ?>"<?= $rewardsActive ? ' aria-current="page"' : '' ?>>Rewards</a>
            <?php endif; ?>
            <a class="<?= $accountActive ? 'active' : '' ?>" href="<?= e(base_url('/account/profile')) ?>"<?= $accountActive ? ' aria-current="page"' : '' ?>>Account</a>
        </nav>

        <div class="header-actions">
            <?php View::partial('components/theme_toggle'); ?>
            <?php if ($gamification !== null && $gamification['streak'] > 0): ?>
                <span class="chip chip-gold" title="<?= (int) $gamification['streak'] ?> day streak">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3c2.5 3 4.5 5 4.5 8a4.5 4.5 0 0 1-9 0c0-1.4.5-2.5 1.4-3.7.6 1 1.3 1.5 2.1 1.7C11.4 7 11.6 5 12 3z"/></svg>
                    <?= (int) $gamification['streak'] ?><span class="chip-full-label">-day streak</span>
                </span>
                <span class="chip chip-header-xp"><?= number_format((int) $gamification['xp']) ?> XP</span>
            <?php endif; ?>
            <?php if ($headerUser): ?>
                <a class="user-chip" href="<?= e(base_url('/account/profile')) ?>" aria-label="Open account settings for <?= e($headerUser['name'] ?? 'your account') ?>">
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
            <button class="icon-btn mobile-menu-button" type="button" data-mobile-menu aria-controls="app-navigation" aria-expanded="false" aria-label="Open navigation menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>
</header>
