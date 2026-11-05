<?php
/**
 * Shared account sub-navigation.
 *
 * @var string $active One of: profile, security, appearance.
 */
$items = [
    'profile' => [
        'href' => '/account/profile',
        'title' => 'Profile',
        'hint' => 'Name and defaults',
        'icon' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-4 3-6 7-6s6.3 2 7 6"/>',
    ],
    'security' => [
        'href' => '/account/security',
        'title' => 'Security',
        'hint' => 'Password and sessions',
        'icon' => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    ],
    'appearance' => [
        'href' => '/account/appearance',
        'title' => 'Appearance',
        'hint' => 'Theme',
        'icon' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 3.5v17a8.5 8.5 0 0 0 0-17Z" fill="currentColor" stroke="none"/>',
    ],
];
?>
<aside class="card account-nav" aria-label="Account settings">
    <?php foreach ($items as $key => $item): ?>
        <a
            class="<?= $active === $key ? 'active' : '' ?>"
            href="<?= e(base_url($item['href'])) ?>"
            <?= $active === $key ? 'aria-current="page"' : '' ?>
        >
            <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><?= $item['icon'] ?></svg></span>
            <div><b><?= e($item['title']) ?></b><small><?= e($item['hint']) ?></small></div>
        </a>
    <?php endforeach; ?>
    <a href="<?= e(base_url('/dashboard')) ?>">
        <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 18-6-6 6-6"/></svg></span>
        <div><b>Dashboard</b><small>Return to your CVs</small></div>
    </a>
</aside>
