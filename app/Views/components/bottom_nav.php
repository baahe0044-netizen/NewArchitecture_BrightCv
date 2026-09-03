<?php
/**
 * Floating bottom navigation, shown on phones and tablets.
 *
 * The header keeps the desktop; below 900px it collapses to this bar, which
 * puts the app's main destinations two taps away on exactly the devices
 * where reach matters most. It links only to routes that already exist -- it
 * adds no surface, and the active item is marked with aria-current so the
 * state is announced rather than only drawn.
 *
 * @var string $active One of: dashboard, templates, rewards, account.
 */
$gamificationOn = Auth::id() && GamificationService::isEnabled();

$items = [
    'dashboard' => [
        'href' => '/dashboard',
        'label' => 'Home',
        'icon' => '<path d="M4 11.2 12 4.5l8 6.7"/><path d="M6.4 10v9.5h11.2V10"/>',
    ],
    'templates' => [
        'href' => '/templates',
        'label' => 'Templates',
        'icon' => '<rect x="4" y="4" width="7" height="7" rx="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.6"/>',
    ],
];

// The centre FAB already goes to the builder, so the fourth slot is
// Rewards where the game layer is on, and falls back to Account -- the
// tab it would otherwise be redundant with -- where it is off.
if ($gamificationOn) {
    $items['rewards'] = [
        'href' => '/rewards',
        'label' => 'Rewards',
        'icon' => '<path d="M6 3h12v6a6 6 0 0 1-12 0z"/><path d="M6 5H3v2a3 3 0 0 0 3 3M18 5h3v2a3 3 0 0 1-3 3"/><path d="M9 21h6M12 15v6"/>',
    ];
}

$items['account'] = [
    'href' => '/account/profile',
    'label' => 'Account',
    'icon' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/>',
];

$active = $active ?? '';
?>
<nav class="bottom-nav" aria-label="Main">
    <div class="bottom-nav-inner">
        <?php $half = (int) ceil(count($items) / 2); $index = 0; ?>
        <?php foreach ($items as $key => $item): ?>
            <?php if ($index === $half): ?>
                <?php /* Raised centre action. It goes to the builder, which
                         already exists: this is a shorter route to the screen
                         people use most, not a new destination. */ ?>
                <a class="bottom-nav-fab" href="<?= e(base_url('/resume/builder')) ?>" aria-label="Start writing a CV">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </a>
            <?php endif; ?>
            <?php $index++; ?>
            <?php $isActive = $key === $active; ?>
            <a
                class="bottom-nav-item"
                href="<?= e(base_url($item['href'])) ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $item['icon'] ?></svg>
                <?php /* The name stays in the markup for every item, so each
                         link keeps an accessible name. It is collapsed rather
                         than removed for the inactive ones, which is what the
                         visual design asks for and what keeps it announced. */ ?>
                <span class="bottom-nav-label"><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
