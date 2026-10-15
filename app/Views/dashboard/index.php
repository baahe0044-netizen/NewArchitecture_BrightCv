<?php
$stats = $dashboard['stats'] ?? [];
$resumes = $dashboard['resumes'] ?? [];
$activity = $dashboard['activity'] ?? [];
$firstName = explode(' ', trim((string) ($user['name'] ?? 'there')))[0] ?: 'there';
$top = $resumes[0] ?? null;
$journey = $gamification['journey'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Dashboard · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('dashboard/dashboard.css')) ?>">
</head>
<body>
<?php View::partial('components/app_header', compact('user', 'gamification')); ?>

<main class="page-shell dashboard-page">
    <div class="container dashboard-container">
        <?php View::partial('components/flash', ['message' => $message ?? null]); ?>

        <section class="dashboard-hero">
            <div>
                <p class="eyebrow">Dashboard</p>
                <h1>Welcome back, <?= e($firstName) ?>.</h1>
                <p>Continue a draft, make a version for a new role, or start a fresh CV.</p>
            </div>
            <button class="btn btn-primary create-cv-button" type="button" data-create-resume>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Create new CV
            </button>
        </section>

        <?php if ($gamification !== null): ?>
            <?php $level = $gamification['level']; ?>
            <section class="level-banner">
                <div class="level-banner-badge">
                    <span class="level-number"><?= (int) $level['level'] ?></span>
                    <div>
                        <p class="level-title">Level <?= (int) $level['level'] ?> &mdash; <?= e($level['name']) ?></p>
                        <p class="level-subtitle"><?= e($level['level'] >= 3 ? 'Your CV holds up to a first read.' : 'Keep building — every section adds up.') ?></p>
                    </div>
                </div>

                <div class="level-banner-progress">
                    <div class="level-banner-progress-row">
                        <span class="xp-label"><?= number_format((int) $gamification['xp']) ?> XP</span>
                        <span class="xp-label muted"><?= number_format((int) $level['xp_to_next']) ?> XP to Level <?= (int) $level['level'] + 1 ?> &mdash; <?= e($level['next_name']) ?></span>
                    </div>
                    <div class="level-banner-bar">
                        <div class="level-banner-fill" style="width:<?= (int) $level['progress_percent'] ?>%"></div>
                    </div>
                    <?php if ((int) $gamification['xp_today'] > 0): ?>
                        <p class="xp-today"><span>+<?= (int) $gamification['xp_today'] ?> XP</span> earned today</p>
                    <?php endif; ?>
                </div>

                <?php $nextTemplate = null; foreach ($gamification['templates'] as $t) { if (!$t['unlocked']) { $nextTemplate = $t; break; } } ?>
                <?php if ($nextTemplate): ?>
                    <div class="level-banner-unlock">
                        <span class="unlock-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12v6a6 6 0 0 1-12 0z"/><path d="M6 5H3v2a3 3 0 0 0 3 3M18 5h3v2a3 3 0 0 1-3 3"/><path d="M9 21h6M12 15v6"/></svg>
                        </span>
                        <div><p class="unlock-title">Next unlock</p><p class="unlock-name">The <?= e($nextTemplate['name']) ?> template</p></div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="stats-grid card" aria-label="CV overview">
            <article class="stat-card">
                <span>Active CVs</span>
                <strong><?= (int) ($stats['total_resumes'] ?? 0) ?></strong>
                <small><?= (int) ($stats['completed_resumes'] ?? 0) ?> marked complete</small>
            </article>
            <article class="stat-card">
                <span>Average completion</span>
                <strong><?= (int) ($stats['average_completion'] ?? 0) ?><small>%</small></strong>
                <small>Across your current CVs</small>
            </article>
            <article class="stat-card">
                <span>Average ATS score</span>
                <strong><?= (int) ($stats['average_ats'] ?? 0) ?><small>%</small></strong>
                <small>Across scanned CVs</small>
            </article>
            <article class="stat-card">
                <span>Exports</span>
                <strong><?= (int) ($stats['total_downloads'] ?? 0) ?></strong>
                <small>Print, PDF, and backup files</small>
            </article>
        </section>

        <?php if ($gamification !== null && $top): ?>
            <section class="card continue-panel">
                <div class="continue-panel-body">
                    <div class="continue-thumb template-preview-<?= e($top['template_key']) ?>" style="--resume-accent:<?= e($top['accent_color']) ?>" aria-hidden="true">
                        <div class="preview-sheet">
                            <b><?= e(mb_strtoupper(mb_substr($top['name'], 0, 24))) ?></b>
                            <span>PROFESSIONAL CV</span><hr><i></i><i></i><em>EXPERIENCE</em><i></i><i></i><em>SKILLS</em><i></i>
                        </div>
                    </div>

                    <div class="continue-main">
                        <div class="continue-heading">
                            <div>
                                <p class="eyebrow">Carry on with</p>
                                <h2><?= e($top['name']) ?></h2>
                                <p class="continue-meta">Edited <?= e(human_time_ago($top['updated_at'])) ?> &middot; <?= e($topTemplateName) ?></p>
                            </div>
                            <div class="progress-ring" style="--ring-percent:<?= (int) $top['completion'] ?>" role="img" aria-label="<?= (int) $top['completion'] ?> percent done">
                                <svg viewBox="0 0 76 76" width="76" height="76" aria-hidden="true">
                                    <circle cx="38" cy="38" r="32" fill="none" stroke="var(--card-2)" stroke-width="9"/>
                                    <circle class="progress-ring-fill" cx="38" cy="38" r="32" fill="none" stroke-width="9" stroke-linecap="round" transform="rotate(-90 38 38)"/>
                                </svg>
                                <span class="progress-ring-label"><b><?= (int) $top['completion'] ?>%</b><small>done</small></span>
                            </div>
                        </div>

                        <ol class="journey-stepper">
                            <?php foreach ($journey as $index => $node): ?>
                                <li class="<?= $node['done'] ? 'is-done' : '' ?> <?= $node['current'] ? 'is-current' : '' ?>">
                                    <span class="journey-node">
                                        <?php if ($node['done'] && !$node['current']): ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"/></svg>
                                        <?php elseif ($node['current']): ?>
                                            <b><?= (int) $node['number'] ?></b>
                                        <?php endif; ?>
                                    </span>
                                    <span class="journey-label"><?= e($node['label']) ?></span>
                                </li>
                                <?php if ($index < count($journey) - 1):
                                    $nextIsCurrent = $journey[$index + 1]['current'] ?? false;
                                    $connectorClass = $nextIsCurrent ? 'is-leading' : ($node['done'] ? 'is-done' : '');
                                ?><li class="journey-connector <?= $connectorClass ?>" aria-hidden="true"></li><?php endif; ?>
                            <?php endforeach; ?>
                        </ol>

                        <div class="continue-actions">
                            <a class="btn btn-primary" href="<?= e(base_url('/resume/builder/' . $top['id'])) ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg>
                                Carry on writing
                            </a>
                            <a class="btn" href="<?= e(base_url('/resume/' . $top['id'] . '/print')) ?>" target="_blank" rel="noopener">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 11 5 5 5-5"/><path d="M5 21h14"/></svg>
                                Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="dashboard-grid">
            <div class="dashboard-main">
                <section class="card resume-library" aria-labelledby="resume-library-title">
                    <div class="section-title-row">
                        <div><h2 id="resume-library-title">Your CVs</h2><p>Open a draft or create a tailored copy for another application.</p></div>
                        <a href="<?= e(base_url('/templates')) ?>">Browse templates</a>
                    </div>

                    <?php if ($resumes): ?>
                        <div class="resume-grid" id="resumeGrid">
                            <?php foreach ($resumes as $resume): ?>
                                <article class="resume-card" data-resume-card="<?= (int) $resume['id'] ?>">
                                    <a class="resume-preview template-preview-<?= e($resume['template_key']) ?>" href="<?= e(base_url('/resume/builder/' . $resume['id'])) ?>" style="--resume-accent:<?= e($resume['accent_color']) ?>" aria-label="Edit <?= e($resume['name']) ?>">
                                        <div class="preview-sheet" aria-hidden="true">
                                            <b><?= e(mb_strtoupper(mb_substr($resume['name'], 0, 24))) ?></b>
                                            <span>PROFESSIONAL CV</span><hr><i></i><i></i><em>EXPERIENCE</em><i></i><i></i><em>SKILLS</em><i></i>
                                        </div>
                                        <div class="resume-score"><b><?= (int) $resume['completion'] ?>%</b><span>complete</span></div>
                                    </a>
                                    <div class="resume-card-body">
                                        <div class="resume-card-heading">
                                            <div>
                                                <h3><?= e($resume['name']) ?></h3>
                                                <p>Updated <?= e(date('M j, Y', strtotime($resume['updated_at']))) ?></p>
                                            </div>
                                            <span class="resume-status <?= $resume['status'] === 'completed' ? 'ready' : '' ?>"><?= $resume['status'] === 'completed' ? 'Done' : e(ucfirst($resume['status'])) ?></span>
                                        </div>
                                        <div class="resume-progress" aria-label="<?= (int) $resume['completion'] ?> percent complete"><i style="width:<?= (int) $resume['completion'] ?>%"></i></div>
                                        <div class="resume-meta"><span><?= (int) $resume['completion'] ?>% complete</span><span>ATS <?= (int) $resume['ats_score'] ?>%</span></div>
                                        <div class="resume-card-actions">
                                            <a href="<?= e(base_url('/resume/builder/' . $resume['id'])) ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19.5V16l10-10 4 4-10 10H4Z"/><path d="m12.5 7.5 4 4"/></svg>
                                                Edit
                                            </a>
                                            <a href="<?= e(base_url('/resume/' . $resume['id'] . '/print')) ?>" target="_blank" rel="noopener">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12s3.3-6 9-6 9 6 9 6-3.3 6-9 6-9-6-9-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                                                Preview / PDF
                                            </a>
                                            <button type="button" data-duplicate="<?= (int) $resume['id'] ?>">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
                                                Duplicate
                                            </button>
                                            <div class="resume-more">
                                                <button class="resume-menu-button" type="button" data-resume-menu="<?= (int) $resume['id'] ?>" aria-haspopup="menu" aria-expanded="false">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="5" cy="12" r="1" fill="currentColor"/><circle cx="12" cy="12" r="1" fill="currentColor"/><circle cx="19" cy="12" r="1" fill="currentColor"/></svg>
                                                    More
                                                </button>
                                                <div class="resume-menu" data-menu-for="<?= (int) $resume['id'] ?>" role="menu">
                                                    <button class="danger-link" type="button" data-delete="<?= (int) $resume['id'] ?>" data-name="<?= e($resume['name']) ?>" role="menuitem">Move to trash</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                            <button class="new-resume-card" type="button" data-create-resume>
                                <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg></span>
                                <b>Start a new run</b>
                                <small><?= $gamification !== null ? 'Tailor a copy for another role. +50 XP' : 'Start with a clean, guided draft.' ?></small>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg viewBox="0 0 24 24" width="26" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 13h6M10 17h4"/></svg>
                            </div>
                            <h3>Create your first CV</h3>
                            <p>Give it a useful name now. You can choose the template and edit every section in the builder.</p>
                            <button class="btn btn-primary" type="button" data-create-resume>Create my first CV</button>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="dashboard-side" aria-label="Dashboard guidance">
                <?php if ($gamification !== null): ?>
                    <?php $doneCount = count(array_filter($gamification['quests'], static fn ($q) => $q['done'])); ?>
                    <section class="quest-panel">
                        <div class="section-title-row compact">
                            <div><h2>Today's three</h2></div>
                            <span class="chip chip-small"><?= $doneCount ?> of <?= count($gamification['quests']) ?></span>
                        </div>
                        <p class="quest-panel-hint">Small jobs that measurably improve the CV.</p>

                        <div class="quest-list">
                            <?php foreach ($gamification['quests'] as $quest): ?>
                                <div class="quest-row <?= $quest['done'] ? 'is-done' : '' ?>">
                                    <span class="quest-check" aria-hidden="true">
                                        <?php if ($quest['done']): ?>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7"/></svg>
                                        <?php endif; ?>
                                    </span>
                                    <span class="quest-label"><?= e($quest['label']) ?></span>
                                    <span class="quest-xp">+<?= (int) $quest['xp'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="card badges-panel">
                        <div class="section-title-row compact">
                            <div><h2>Badges</h2></div>
                            <a href="<?= e(base_url('/rewards')) ?>">All <?= (int) $gamification['badges']['total'] ?> &rarr;</a>
                        </div>
                        <p class="quest-panel-hint"><?= (int) $gamification['badges']['earned_count'] ?> of <?= (int) $gamification['badges']['total'] ?> earned.</p>

                        <div class="badge-grid">
                            <?php $shown = 0; foreach ($gamification['badges']['list'] as $badge): if (!$badge['earned'] || $shown >= 4) continue; $shown++; ?>
                                <div class="badge">
                                    <span class="badge-tile badge-<?= e($badge['colour']) ?>"><?= badge_icon($badge['key']) ?></span>
                                    <span class="badge-name"><?= e($badge['name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($gamification['badges']['list'] as $badge): if ($badge['earned'] || $shown >= 4) continue; $shown++; ?>
                                <div class="badge">
                                    <span class="badge-locked" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
                                    <span class="badge-name muted"><?= e($badge['name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="card next-step-card">
                    <div class="side-card-heading">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M12 2V5M22 12h-3M12 22v-3M2 12h3"/></svg></span>
                        <div><h2>Recommended next step</h2><p>Based on your latest CV</p></div>
                    </div>
                    <?php if (!$resumes): ?>
                        <h3>Start with your most recent role</h3>
                        <p>Add your title, employer, and two outcomes. You can return to the other sections later.</p>
                        <button class="btn btn-primary btn-small" type="button" data-create-resume>Begin now</button>
                    <?php else: ?>
                        <?php $next = $resumes[0]; ?>
                        <h3><?= (int) $next['completion'] < 80 ? 'Finish your latest draft' : 'Tailor this CV for a role' ?></h3>
                        <p><?= (int) $next['completion'] < 80 ? 'Complete the highlighted sections, then review the ATS feedback.' : 'Add a job description and review role-specific keywords before applying.' ?></p>
                        <a class="btn btn-primary btn-small" href="<?= e(base_url('/resume/builder/' . $next['id'])) ?>">Open <?= e($next['name']) ?></a>
                    <?php endif; ?>
                </section>

                <section class="card activity-card">
                    <div class="section-title-row compact"><div><h2>Recent activity</h2><p>Your latest account events</p></div></div>
                    <?php if ($activity): ?>
                        <div class="activity-list">
                            <?php foreach ($activity as $item): ?>
                                <div class="activity-item">
                                    <span aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/></svg>
                                    </span>
                                    <div><b><?= e($item['description']) ?></b><small><?= e(date('M j · g:i a', strtotime($item['created_at']))) ?></small></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="muted">Your editing and export activity will appear here.</p>
                    <?php endif; ?>
                </section>

                <section class="support-callout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <p>Stuck on the wording? <a href="mailto:<?= e(SUPPORT_EMAIL) ?>?subject=<?= e(rawurlencode('Help with my CV')) ?>">Ask a real person</a></p>
                </section>
            </aside>
        </section>
    </div>
</main>
<?php View::partial('components/bottom_nav', ['active' => 'dashboard']); ?>

<div class="modal" id="createResumeModal" role="dialog" aria-modal="true" aria-labelledby="createResumeTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header">
            <div><p class="eyebrow">New CV</p><h2 id="createResumeTitle">What is this CV for?</h2></div>
            <button class="icon-btn" type="button" data-modal-close="createResumeModal" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <form id="createResumeForm">
            <div class="field">
                <label for="resumeName">CV name</label>
                <input id="resumeName" name="name" maxlength="150" placeholder="e.g. Software Engineering CV" required>
                <span class="field-hint">Use the role or company name so tailored versions are easy to find.</span>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" type="button" data-modal-close="createResumeModal">Cancel</button>
                <button class="btn btn-primary" type="submit">Create CV</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="deleteResumeModal" role="dialog" aria-modal="true" aria-labelledby="deleteResumeTitle" aria-describedby="deleteResumeMessage" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header"><div><p class="eyebrow">Confirm deletion</p><h2 id="deleteResumeTitle">Move this CV to trash?</h2></div></div>
        <p class="muted" id="deleteResumeMessage">This CV will no longer appear in your workspace.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button" data-modal-close="deleteResumeModal">Keep CV</button>
            <button class="btn btn-danger" type="button" id="confirmDeleteResume">Move to trash</button>
        </div>
    </div>
</div>

<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
<script src="<?= e(asset('dashboard/dashboard.js')) ?>" defer></script>
</body>
</html>
