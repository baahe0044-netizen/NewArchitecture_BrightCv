<?php
$level = $gamification['level'];
$badges = $gamification['badges'];
$templates = $gamification['templates'];
$closest = $badges['closest'];

// The five-node ladder Rewards.dc.html shows, extended past Interviewer with
// the same step GamificationService uses so a high-level account still sees
// a coherent ladder rather than one that stops.
$ladder = [
    ['level' => 1, 'name' => 'Starter', 'xp' => 0],
    ['level' => 2, 'name' => 'Drafter', 'xp' => 400],
    ['level' => 3, 'name' => 'Contender', 'xp' => 1000],
    ['level' => 4, 'name' => 'Shortlister', 'xp' => 1500],
    ['level' => 5, 'name' => 'Interviewer', 'xp' => 2200],
];
if ((int) $level['level'] > 5) {
    $ladder[3] = ['level' => (int) $level['level'] - 1, 'name' => $level['name'], 'xp' => $level['xp_floor'] - 900];
    $ladder[4] = ['level' => (int) $level['level'], 'name' => $level['name'], 'xp' => $level['xp_floor']];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Rewards · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('rewards/rewards.css')) ?>">
</head>
<body>
<?php View::partial('components/app_header', compact('user', 'gamification')); ?>

<main class="page-shell rewards-page">
    <div class="container rewards-container">
        <?php View::partial('components/flash', ['message' => $message ?? null]); ?>

        <div class="rewards-heading">
            <div>
                <h1>Rewards</h1>
                <p>Every badge here comes from something that measurably improved a CV.</p>
            </div>
            <span class="chip chip-large"><?= (int) $badges['earned_count'] ?> of <?= (int) $badges['total'] ?> badges &middot; <?= count(array_filter($templates, static fn ($t) => $t['unlocked'])) ?> of <?= count($templates) ?> templates</span>
        </div>

        <section class="level-ladder">
            <?php foreach ($ladder as $index => $rung): ?>
                <?php
                $isCurrent = $rung['level'] === (int) $level['level'];
                $isPast = $rung['level'] < (int) $level['level'];
                $state = $isCurrent ? 'is-current' : ($isPast ? 'is-past' : 'is-future');
                ?>
                <div class="ladder-node <?= $state ?>">
                    <span class="ladder-number"><?= $isPast ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"/></svg>' : (int) $rung['level'] ?></span>
                    <span class="ladder-name"><?= e($rung['name']) ?></span>
                    <span class="ladder-xp"><?= number_format((int) $rung['xp']) ?> XP<?= $isCurrent ? ' · you are here' : '' ?></span>
                </div>
                <?php if ($index < count($ladder) - 1): ?>
                    <span class="ladder-connector <?= $isPast || $isCurrent ? 'is-crossed' : '' ?>" aria-hidden="true"></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>

        <div class="rewards-grid">
            <section class="card badges-full-panel">
                <div class="section-title-row">
                    <div><h2>Badges</h2></div>
                    <span class="rewards-hint"><?= (int) $badges['earned_count'] ?> earned, <?= (int) $badges['total'] - (int) $badges['earned_count'] ?> to go</span>
                </div>

                <div class="badge-full-grid">
                    <?php foreach ($badges['list'] as $badge): ?>
                        <div class="badge">
                            <?php if ($badge['earned']): ?>
                                <span class="badge-tile badge-<?= e($badge['colour']) ?>"><?= badge_icon($badge['key']) ?></span>
                                <span class="badge-name"><?= e($badge['name']) ?></span>
                            <?php else: ?>
                                <span class="badge-locked" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                                <span class="badge-name muted"><?= e($badge['name']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($closest): ?>
                    <div class="closest-badge-callout">
                        <span class="closest-badge-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                        <p><b>Closest badge: <?= e($closest['badge']) ?>.</b> <?= e($closest['message']) ?></p>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="rewards-side">
                <section class="card templates-panel">
                    <h2>Templates</h2>
                    <p class="rewards-hint">New layouts open up as you level.</p>

                    <div class="template-unlock-list">
                        <?php foreach ($templates as $template): ?>
                            <div class="template-unlock-row <?= $template['unlocked'] ? 'is-unlocked' : 'is-locked' ?>">
                                <span class="template-unlock-thumb" style="--tpl-color:<?= e($template['color']) ?>" aria-hidden="true">
                                    <?php if ($template['unlocked']): ?>
                                        <span class="doc-bar" style="background:var(--tpl-color)"></span>
                                        <span class="doc-line"></span><span class="doc-line short"></span><span class="doc-line"></span>
                                    <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                    <?php endif; ?>
                                </span>
                                <div class="template-unlock-body">
                                    <h3><?= e($template['name']) ?></h3>
                                    <p><?= $template['unlocked'] ? 'Unlocked at Level ' . (int) $template['unlock_level'] : 'Opens at Level ' . (int) $template['unlock_level'] ?></p>
                                </div>
                                <?php if ($template['unlocked'] && $topResumeId): ?>
                                    <a class="btn btn-small" href="<?= e(base_url('/resume/builder/' . $topResumeId)) ?>" title="Opens the builder, where Design has this template">Use</a>
                                <?php elseif (!$template['unlocked']): ?>
                                    <span class="chip chip-small chip-locked-xp"><?= number_format(max(0, (int) $template['unlock_xp'] - (int) $gamification['xp'])) ?> XP</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="transparency-panel">
                    <h2>A word on all this</h2>
                    <p>Points are only worth something if they track real quality. Every XP here is tied to a change that makes your CV read better &mdash; nothing is awarded for simply logging in. You can switch the whole game layer off in <a href="<?= e(base_url('/account/appearance')) ?>">Account</a>.</p>
                </section>
            </aside>
        </div>
    </div>
</main>
<?php View::partial('components/bottom_nav', ['active' => 'rewards']); ?>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
</body>
</html>
