<?php
$resumes = $dashboard['resumes'] ?? [];
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
                                <b>Start a new CV</b>
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

            <section class="support-callout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <p>Stuck on the wording? <a href="mailto:<?= e(SUPPORT_EMAIL) ?>?subject=<?= e(rawurlencode('Help with my CV')) ?>">Ask a real person</a></p>
            </section>
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
