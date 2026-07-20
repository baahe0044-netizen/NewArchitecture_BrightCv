<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
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
                <p class="eyebrow">Professional starting points</p>
                <h1>Choose a template that fits your story.</h1>
                <p>Every design is A4-ready, responsive, and structured for applicant tracking systems. Change it at any time without losing content.</p>
            </div>
            <div class="template-fact"><strong><?= count($templates) ?></strong><span>carefully designed layouts</span></div>
        </section>

        <section class="template-toolbar" aria-label="Template filters">
            <div class="filter-tabs" role="tablist">
                <button class="active" type="button" data-template-filter="all">All templates</button>
                <?php foreach (array_values(array_unique(array_column($templates, 'category'))) as $category): ?>
                    <button type="button" data-template-filter="<?= e(mb_strtolower($category)) ?>"><?= e($category) ?></button>
                <?php endforeach; ?>
            </div>
            <label class="template-search">
                <span class="sr-only">Search templates</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" id="templateSearch" placeholder="Search templates">
            </label>
        </section>

        <section class="templates-grid" id="templatesGrid">
            <?php foreach ($templates as $template): ?>
                <article class="template-card" data-template-card data-category="<?= e(mb_strtolower($template['category'])) ?>" data-name="<?= e(mb_strtolower($template['name'])) ?>">
                    <div class="template-preview template-style-<?= e($template['template_key']) ?>" style="--accent:<?= e($template['color']) ?>">
                        <div class="template-paper">
                            <header><div class="template-avatar">AM</div><div><h2>ALEX MORGAN</h2><span>PRODUCT &amp; OPERATIONS LEAD</span></div></header>
                            <div class="template-contact">Accra, Ghana · alex@example.com · +233 24 000 0000</div>
                            <div class="template-content">
                                <section><h3>PROFILE</h3><i></i><i></i><i class="short"></i></section>
                                <section><h3>EXPERIENCE</h3><b>Operations Manager</b><small>Forward Labs · 2022 — Present</small><i></i><i></i><i class="short"></i></section>
                                <section><h3>EDUCATION</h3><b>Bachelor of Arts</b><small>University of Ghana</small></section>
                                <section><h3>SKILLS</h3><div class="preview-skills"><span>Leadership</span><span>Strategy</span><span>Analytics</span></div></section>
                            </div>
                        </div>
                        <span class="category-badge"><?= e($template['category']) ?></span>
                        <?php if ((int) $template['is_premium'] === 1): ?><span class="premium-badge">Pro</span><?php endif; ?>
                        <div class="template-overlay">
                            <button class="btn btn-secondary btn-small" type="button" data-preview-template="<?= e($template['template_key']) ?>" data-template-name="<?= e($template['name']) ?>">Preview</button>
                            <button class="btn btn-primary btn-small" type="button" data-use-template="<?= e($template['template_key']) ?>" data-template-name="<?= e($template['name']) ?>">Use template</button>
                        </div>
                    </div>
                    <div class="template-card-body">
                        <div><h2><?= e($template['name']) ?></h2><span><?= e($template['category']) ?></span></div>
                        <p><?= e($template['description']) ?></p>
                        <div class="template-features"><span>✓ ATS-friendly</span><span>✓ A4 print</span><span>✓ Custom colours</span></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
        <div class="empty-state template-empty" id="templateEmpty" hidden>
            <div class="empty-state-icon">⌕</div><h3>No matching templates</h3><p>Try a different category or search term.</p>
        </div>
    </div>
</main>

<div class="modal template-modal" id="templatePreviewModal" role="dialog" aria-modal="true" aria-labelledby="previewTemplateTitle" aria-hidden="true">
    <div class="modal-dialog preview-dialog">
        <div class="modal-header">
            <div><p class="eyebrow">Full preview</p><h2 id="previewTemplateTitle">Template preview</h2></div>
            <button class="icon-btn" type="button" data-modal-close="templatePreviewModal" aria-label="Close">×</button>
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
            <button class="icon-btn" type="button" data-modal-close="nameTemplateResumeModal" aria-label="Close">×</button>
        </div>
        <form id="templateResumeForm">
            <input type="hidden" id="selectedTemplateKey" value="modern">
            <div class="field">
                <label for="templateResumeName">CV name</label>
                <input id="templateResumeName" maxlength="150" placeholder="e.g. Product Manager CV" required>
                <span class="field-hint">A clear name makes tailored versions easier to manage.</span>
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
