<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>CV templates · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('templates/template.css')) ?>">
</head>
<body>
<?php View::partial('components/app_header', compact('user')); ?>

<main class="page-shell templates-page">
    <div class="container">
        <section class="templates-hero">
            <div>
                <p class="eyebrow">CV templates</p>
                <h1>Choose a clear structure for your experience.</h1>
                <p>Every template uses the same CV content and is prepared for A4 printing. You can change the layout later without starting over.</p>
            </div>
            <a class="btn btn-secondary" href="<?= e(base_url('/dashboard')) ?>">Back to dashboard</a>
        </section>

        <section class="template-toolbar" aria-label="Filter CV templates">
            <div class="filter-group">
                <span class="filter-label">Category</span>
                <div class="filter-tabs" role="group" aria-label="Template categories">
                    <button class="active" type="button" data-template-filter="all" aria-pressed="true">All</button>
                    <?php foreach (array_values(array_unique(array_column($templates, 'category'))) as $category): ?>
                        <button type="button" data-template-filter="<?= e(mb_strtolower($category)) ?>" aria-pressed="false"><?= e($category) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <label class="template-search" for="templateSearch">
                <span class="filter-label">Search templates</span>
                <span class="search-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input type="search" id="templateSearch" placeholder="Search by name or category">
                </span>
            </label>
        </section>

        <div class="template-results-row">
            <p id="templateResults" role="status" aria-live="polite"><?= count($templates) ?> <?= count($templates) === 1 ? 'template' : 'templates' ?></p>
            <p>Select <strong>Preview</strong> to inspect a layout or <strong>Use this template</strong> to create a new CV.</p>
        </div>

        <section class="templates-grid" id="templatesGrid" aria-label="Available CV templates">
            <?php foreach ($templates as $template): ?>
                <article class="template-card" data-template-card data-category="<?= e(mb_strtolower($template['category'])) ?>" data-name="<?= e(mb_strtolower($template['name'])) ?>">
                    <div class="template-preview template-style-<?= e($template['template_key']) ?>" style="--accent:<?= e($template['color']) ?>" aria-label="<?= e($template['name']) ?> layout preview">
                        <div class="template-paper" aria-hidden="true">
                            <header><div class="template-avatar">AM</div><div><h2>ALEX MORGAN</h2><span>PRODUCT &amp; OPERATIONS LEAD</span></div></header>
                            <div class="template-contact">Accra, Ghana · alex@example.com · +233 24 000 0000</div>
                            <div class="template-content">
                                <section><h3>PROFILE</h3><i></i><i></i><i class="short"></i></section>
                                <section><h3>EXPERIENCE</h3><b>Operations Manager</b><small>Forward Labs · 2022 — Present</small><i></i><i></i><i class="short"></i></section>
                                <section><h3>EDUCATION</h3><b>Bachelor of Arts</b><small>University of Ghana</small></section>
                                <section><h3>SKILLS</h3><div class="preview-skills"><span>Leadership</span><span>Strategy</span><span>Analytics</span></div></section>
                            </div>
                        </div>
                    </div>
                    <div class="template-card-body">
                        <div class="template-name-row">
                            <div><h2><?= e($template['name']) ?></h2><span><?= e($template['category']) ?></span></div>
                            <?php if ((int) $template['is_premium'] === 1): ?><span class="premium-label">Pro</span><?php endif; ?>
                        </div>
                        <p><?= e($template['description']) ?></p>
                        <p class="template-features">ATS-friendly · A4 print · Custom colours</p>
                        <div class="template-card-actions">
                            <button class="btn btn-secondary btn-small" type="button" data-preview-template="<?= e($template['template_key']) ?>" data-template-name="<?= e($template['name']) ?>">Preview</button>
                            <button class="btn btn-primary btn-small" type="button" data-use-template="<?= e($template['template_key']) ?>" data-template-name="<?= e($template['name']) ?>">Use this template</button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <div class="empty-state template-empty" id="templateEmpty" hidden>
            <div class="empty-state-icon">
                <svg viewBox="0 0 24 24" width="25" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M8.5 11h5"/></svg>
            </div>
            <h3>No matching templates</h3>
            <p>Clear the search or choose another category.</p>
        </div>
    </div>
</main>

<div class="modal template-modal" id="templatePreviewModal" role="dialog" aria-modal="true" aria-labelledby="previewTemplateTitle" aria-hidden="true">
    <div class="modal-dialog preview-dialog">
        <div class="modal-header">
            <div><p class="eyebrow">Template preview</p><h2 id="previewTemplateTitle">Template preview</h2></div>
            <button class="icon-btn" type="button" data-modal-close="templatePreviewModal" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div id="largeTemplatePreview" class="large-template-preview"></div>
        <div class="modal-actions">
            <button class="btn btn-secondary" type="button" data-modal-close="templatePreviewModal">Close</button>
            <button class="btn btn-primary" type="button" id="usePreviewedTemplate">Use this template</button>
        </div>
    </div>
</div>

<div class="modal" id="nameTemplateResumeModal" role="dialog" aria-modal="true" aria-labelledby="nameTemplateResumeTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header">
            <div><p class="eyebrow">Create from template</p><h2 id="nameTemplateResumeTitle">Name your new CV</h2></div>
            <button class="icon-btn" type="button" data-modal-close="nameTemplateResumeModal" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <form id="templateResumeForm">
            <input type="hidden" id="selectedTemplateKey" value="modern">
            <div class="field">
                <label for="templateResumeName">CV name</label>
                <input id="templateResumeName" maxlength="150" placeholder="e.g. Product Manager CV" required>
                <span class="field-hint">Use the target role or company so tailored versions are easy to find.</span>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" type="button" data-modal-close="nameTemplateResumeModal">Cancel</button>
                <button class="btn btn-primary" type="submit">Create and edit</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('templates/template.js')) ?>" defer></script>
</body>
</html>
