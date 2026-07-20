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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
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
                <p class="eyebrow">Your career workspace</p>
                <h1>Welcome back, <?= e($firstName) ?>.</h1>
                <p>Keep your CV current, tailor it for the right role, and apply with confidence.</p>
            </div>
            <button class="btn btn-primary create-cv-button" type="button" data-create-resume>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Create a new CV
            </button>
        </section>

        <section class="stats-grid" aria-label="CV statistics">
            <article class="stat-card">
                <span class="stat-icon purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 13h6M10 17h6"/></svg></span>
                <div><strong><?= (int) ($stats['total_resumes'] ?? 0) ?></strong><span>Active CVs</span></div>
                <small><?= (int) ($stats['completed_resumes'] ?? 0) ?> application-ready</small>
            </article>
            <article class="stat-card">
                <span class="stat-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.6 2.6L16.5 9"/></svg></span>
                <div><strong><?= (int) ($stats['average_ats'] ?? 0) ?><small>%</small></strong><span>Average ATS score</span></div>
                <small>Across scanned CVs</small>
            </article>
            <article class="stat-card">
                <span class="stat-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 19h14"/></svg></span>
                <div><strong><?= (int) ($stats['total_downloads'] ?? 0) ?></strong><span>Exports</span></div>
                <small>PDF, print, and backup files</small>
            </article>
            <article class="stat-card">
                <span class="stat-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></svg></span>
                <div><strong><?= (int) ($stats['average_completion'] ?? 0) ?><small>%</small></strong><span>Average completion</span></div>
                <small>Keep filling the important gaps</small>
            </article>
        </section>

        <section class="dashboard-grid">
            <div class="dashboard-main">
                <section class="card resume-library">
                    <div class="section-title-row">
                        <div><h2>Your CVs</h2><p>Open a draft or tailor a copy for a new role.</p></div>
                        <a href="<?= e(base_url('/templates')) ?>">Browse templates →</a>
                    </div>

                    <?php if ($resumes): ?>
                        <div class="resume-grid" id="resumeGrid">
                            <?php foreach ($resumes as $resume): ?>
                                <article class="resume-card" data-resume-card="<?= (int) $resume['id'] ?>">
                                    <a class="resume-preview template-preview-<?= e($resume['template_key']) ?>" href="<?= e(base_url('/resume/builder/' . $resume['id'])) ?>" style="--resume-accent:<?= e($resume['accent_color']) ?>">
                                        <div class="preview-sheet">
                                            <b><?= e(mb_strtoupper(mb_substr($resume['name'], 0, 24))) ?></b>
                                            <span>PROFESSIONAL CV</span><hr><i></i><i></i><em>EXPERIENCE</em><i></i><i></i><em>SKILLS</em><i></i>
                                        </div>
                                        <div class="resume-score"><b><?= (int) $resume['completion'] ?>%</b><span>complete</span></div>
                                    </a>
                                    <div class="resume-card-body">
                                        <div class="resume-card-heading">
                                            <div><h3><?= e($resume['name']) ?></h3><p>Updated <?= e(date('M j, Y', strtotime($resume['updated_at']))) ?></p></div>
                                            <button class="icon-btn resume-menu-button" type="button" data-resume-menu="<?= (int) $resume['id'] ?>" aria-label="CV actions">•••</button>
                                            <div class="resume-menu" data-menu-for="<?= (int) $resume['id'] ?>">
                                                <a href="<?= e(base_url('/resume/builder/' . $resume['id'])) ?>">Edit CV</a>
                                                <button type="button" data-duplicate="<?= (int) $resume['id'] ?>">Duplicate</button>
                                                <a href="<?= e(base_url('/resume/' . $resume['id'] . '/print')) ?>" target="_blank">Print / PDF</a>
                                                <button class="danger-link" type="button" data-delete="<?= (int) $resume['id'] ?>" data-name="<?= e($resume['name']) ?>">Move to trash</button>
                                            </div>
                                        </div>
                                        <div class="resume-meta">
                                            <span class="status-pill <?= $resume['status'] === 'completed' ? 'ready' : '' ?>"><?= e(ucfirst($resume['status'])) ?></span>
                                            <span>ATS <?= (int) $resume['ats_score'] ?>%</span>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                            <button class="new-resume-card" type="button" data-create-resume>
                                <span>+</span><b>Create another CV</b><small>Start with a clean, guided draft</small>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <svg viewBox="0 0 24 24" width="26" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h8l4 4v14H7z"/><path d="M15 3v5h5M10 13h6M10 17h4"/></svg>
                            </div>
                            <h3>Create your first professional CV</h3>
                            <p>Choose a name now—you can change the design and every detail later.</p>
                            <button class="btn btn-primary" type="button" data-create-resume>Create my first CV</button>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <aside class="dashboard-side">
                <section class="card next-step-card">
                    <div class="side-card-heading"><span>✦</span><div><h2>Recommended next step</h2><p>Based on your workspace</p></div></div>
                    <?php if (!$resumes): ?>
                        <h3>Start with your strongest recent role</h3>
                        <p>Add your title, employer, and two outcomes. The rest of the CV becomes much easier.</p>
                        <button class="btn btn-primary btn-small" type="button" data-create-resume>Begin now</button>
                    <?php else: ?>
                        <?php $next = $resumes[0]; ?>
                        <h3><?= (int) $next['completion'] < 80 ? 'Finish your most recent draft' : 'Tailor your CV for a role' ?></h3>
                        <p><?= (int) $next['completion'] < 80 ? 'Complete the highlighted sections, then run an ATS scan.' : 'Paste a job description to check role-specific keywords before applying.' ?></p>
                        <a class="btn btn-primary btn-small" href="<?= e(base_url('/resume/builder/' . $next['id'])) ?>">Open <?= e($next['name']) ?></a>
                    <?php endif; ?>
                </section>

                <section class="card activity-card">
                    <div class="section-title-row compact"><div><h2>Recent activity</h2><p>Your latest account events</p></div></div>
                    <?php if ($activity): ?>
                        <div class="activity-list">
                            <?php foreach ($activity as $item): ?>
                                <div class="activity-item">
                                    <span><?= in_array($item['action'], ['resume_created', 'resume_saved'], true) ? '✎' : (str_contains($item['action'], 'export') ? '↓' : '✓') ?></span>
                                    <div><b><?= e($item['description']) ?></b><small><?= e(date('M j · g:i a', strtotime($item['created_at']))) ?></small></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="muted">Your editing and export activity will appear here.</p>
                    <?php endif; ?>
                </section>

                <section class="card dashboard-tip">
                    <span>Quick tip</span>
                    <p>Strong CV bullets usually contain an action, the work you did, and a measurable result.</p>
                </section>
            </aside>
        </section>
    </div>
</main>

<div class="modal" id="createResumeModal" role="dialog" aria-modal="true" aria-labelledby="createResumeTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header">
            <div><p class="eyebrow">New document</p><h2 id="createResumeTitle">What is this CV for?</h2></div>
            <button class="icon-btn" type="button" data-modal-close="createResumeModal" aria-label="Close">×</button>
        </div>
        <form id="createResumeForm">
            <div class="field">
                <label for="resumeName">CV name</label>
                <input id="resumeName" name="name" maxlength="150" placeholder="e.g. Software Engineering CV" required>
                <span class="field-hint">Use a name that helps you remember the target role or company.</span>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" type="button" data-modal-close="createResumeModal">Cancel</button>
                <button class="btn btn-primary" type="submit">Create CV</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="deleteResumeModal" role="dialog" aria-modal="true" aria-labelledby="deleteResumeTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header"><div><p class="eyebrow">Confirm action</p><h2 id="deleteResumeTitle">Move this CV to trash?</h2></div></div>
        <p class="muted" id="deleteResumeMessage">This CV will no longer appear in your workspace.</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button" data-modal-close="deleteResumeModal">Keep CV</button>
            <button class="btn btn-danger" type="button" id="confirmDeleteResume">Move to trash</button>
        </div>
    </div>
</div>

<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('dashboard/dashboard.js')) ?>" defer></script>
</body>
</html>
