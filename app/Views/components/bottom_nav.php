<?php
/**
 * Floating bottom navigation, shown on phones and tablets.
 *
 * The header keeps the desktop; below 900px it collapses to a menu, which
 * puts the app's main destinations two taps away on exactly the devices where
 * reach matters most. This bar links only to routes that already exist -- it
 * adds no surface, and the active item is marked with aria-current so the
 * state is announced rather than only drawn.
 *
 * @var string $active One of: dashboard, templates, builder, account.
 */
$items = [
    'dashboard' => [
        'href' => '/dashboard',
        'label' => 'Home',
        'icon' => '<path d="M4 11.2 12 4.5l8 6.7"/><path d="M6.4 10v9.5h11.2V10"/>',
    ],
    'templates' => [
        'href' => '/templates',
        'label' => 'Designs',
        'icon' => '<rect x="4" y="4" width="7" height="7" rx="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.6"/>',
    ],
    'builder' => [
        'href' => '/resume/builder',
        'label' => 'Build',
        'icon' => '<path d="M5 19.5 8 19l10-10a2.1 2.1 0 0 0-3-3L5 16Z"/><path d="M14.5 7.5 16.5 9.5"/>',
    ],
    'account' => [
        'href' => '/account/profile',
        'label' => 'Account',
        'icon' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/>',
    ],
];

$active = $active ?? '';
?>
<nav class="bottom-nav" aria-label="Main">
    <div class="bottom-nav-inner">
        <?php foreach ($items as $key => $item): ?>
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
