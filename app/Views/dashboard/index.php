<?php
$stats = $dashboard['stats'] ?? [];
$resumes = $dashboard['resumes'] ?? [];
$activity = $dashboard['activity'] ?? [];
$firstName = explode(' ', trim((string) ($user['name'] ?? 'there')))[0] ?: 'there';
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
<?php View::partial('components/app_header', compact('user')); ?>

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
                                            <span class="resume-status <?= $resume['status'] === 'completed' ? 'ready' : '' ?>"><?= e(ucfirst($resume['status'])) ?></span>
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
                                <b>Create another CV</b>
                                <small>Start with a clean, guided draft.</small>
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

                <section class="dashboard-tip">
                    <span>Writing tip</span>
                    <p>A strong experience bullet explains what you did and what changed because of it.</p>
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
