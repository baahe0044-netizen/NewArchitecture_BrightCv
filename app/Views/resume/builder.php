<?php
$builderPayload = [
    'resume' => $resume,
    'templates' => $templates,
    'endpoints' => [
        'save' => '/api/resumes/' . $resume['id'],
        'ats' => '/api/resumes/' . $resume['id'] . '/ats',
        'assistant' => '/api/resumes/' . $resume['id'] . '/assistant',
        'export' => '/api/resumes/' . $resume['id'] . '/export',
        'print' => base_url('/resume/' . $resume['id'] . '/print'),
        'dashboard' => base_url('/dashboard'),
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('resume/preview.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('resume/builder.css')) ?>">
</head>
<body class="builder-page">
<header class="builder-topbar">
    <div class="builder-brand-group">
        <a class="builder-back" href="<?= e(base_url('/dashboard')) ?>" title="Back to dashboard" aria-label="Back to dashboard">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <?php View::partial('components/logo'); ?>
        <span class="topbar-divider"></span>
        <label class="resume-title-field">
            <span class="sr-only">CV name</span>
            <input id="resumeTitle" maxlength="150" value="<?= e($resume['name']) ?>" aria-label="CV name">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
        </label>
    </div>

    <div class="save-indicator" id="saveIndicator" data-state="saved" role="status" aria-live="polite">
        <span class="save-dot"></span><span id="saveStatusText">All changes saved</span>
    </div>

    <div class="builder-actions">
        <button class="icon-btn" id="undoButton" type="button" title="Undo (Ctrl+Z)" aria-label="Undo" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 7 4 12l5 5M5 12h9a5 5 0 0 1 5 5v1"/></svg>
        </button>
        <button class="icon-btn" id="redoButton" type="button" title="Redo (Ctrl+Shift+Z)" aria-label="Redo" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 7 5 5-5 5M19 12h-9a5 5 0 0 0-5 5v1"/></svg>
        </button>
        <button class="btn btn-secondary builder-secondary-action" id="importButton" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 20h14"/></svg>
            Import
        </button>
        <input class="sr-only" id="importFile" type="file" accept="application/json,.json">
        <button class="btn btn-secondary builder-secondary-action" id="exportJsonButton" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 15V3M7 8l5-5 5 5"/><path d="M5 20h14"/></svg>
            Backup
        </button>
        <button class="btn btn-primary" id="printButton" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg>
            Print / PDF
        </button>
        <button class="icon-btn builder-mobile-panel" type="button" data-toggle-panel="assistant" aria-label="Open assistant">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 9.7 8.7 4 11l5.7 2.3L12 19l2.3-5.7L20 11l-5.7-2.3Z"/></svg>
        </button>
    </div>
</header>

<main class="builder-layout">
    <aside class="editor-panel" id="editorPanel" aria-label="CV editor">
        <div class="panel-mobile-header">
            <b>Edit CV</b>
            <button class="icon-btn" type="button" data-close-panel="editor" aria-label="Close editor">×</button>
        </div>
        <div class="editor-mode-tabs" role="tablist">
            <button class="active" type="button" data-editor-mode="content">Content</button>
            <button type="button" data-editor-mode="design">Design</button>
        </div>

        <div class="editor-content-mode" id="editorContentMode">
            <div class="completion-summary">
                <div class="completion-copy"><span>CV progress</span><b id="completionPercent"><?= (int) $resume['completion'] ?>%</b></div>
                <div class="completion-bar"><i id="completionBar" style="width:<?= (int) $resume['completion'] ?>%"></i></div>
                <small id="completionHint">Complete the highlighted sections to strengthen your CV.</small>
            </div>

            <nav class="section-nav" id="sectionNav" aria-label="CV sections">
                <button class="active" type="button" data-section="personal"><span>01</span><div><b>Personal details</b><small>Name and contact</small></div><i data-section-check="personal">○</i></button>
                <button type="button" data-section="summary"><span>02</span><div><b>Summary</b><small>Your professional value</small></div><i data-section-check="summary">○</i></button>
                <button type="button" data-section="experience"><span>03</span><div><b>Experience</b><small>Roles and achievements</small></div><i data-section-check="experience">○</i></button>
                <button type="button" data-section="education"><span>04</span><div><b>Education</b><small>Qualifications</small></div><i data-section-check="education">○</i></button>
                <button type="button" data-section="skills"><span>05</span><div><b>Skills</b><small>Relevant strengths</small></div><i data-section-check="skills">○</i></button>
                <button type="button" data-section="projects"><span>06</span><div><b>Projects</b><small>Practical evidence</small></div><i data-section-check="projects">○</i></button>
                <button type="button" data-section="extras"><span>07</span><div><b>More sections</b><small>Languages and more</small></div><i data-section-check="extras">○</i></button>
            </nav>

            <section class="section-editor" id="sectionEditor" aria-live="polite"></section>
        </div>

        <div class="editor-design-mode" id="editorDesignMode" hidden>
            <div class="design-heading"><p class="eyebrow">Visual style</p><h2>Make it feel like you.</h2><p>Every option remains professional and print-safe.</p></div>

            <div class="design-group">
                <label>Template</label>
                <div class="mini-template-grid">
                    <?php foreach ($templates as $template): ?>
                        <button class="mini-template <?= $resume['template_key'] === $template['template_key'] ? 'active' : '' ?>" type="button" data-template-key="<?= e($template['template_key']) ?>" title="<?= e($template['name']) ?>">
                            <span class="mini-sheet mini-<?= e($template['template_key']) ?>" style="--mini-accent:<?= e($template['color']) ?>"><i></i><i></i><b></b><i></i><i></i></span>
                            <small><?= e($template['name']) ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="design-group">
                <label>Accent colour</label>
                <div class="color-options">
                    <?php foreach (['#5b4df7', '#16324f', '#087f5b', '#e25241', '#b16a00', '#202124', '#7b2fbe', '#075985'] as $color): ?>
                        <button type="button" data-accent-color="<?= e($color) ?>" style="--swatch:<?= e($color) ?>" aria-label="Use <?= e($color) ?>"></button>
                    <?php endforeach; ?>
                    <label class="custom-color" title="Custom colour"><input id="customColor" type="color" value="<?= e($resume['accent_color']) ?>"><span>+</span></label>
                </div>
            </div>

            <div class="design-group">
                <div class="field"><label for="fontFamily">Typeface</label>
                    <select id="fontFamily" data-resume-setting="font_family">
                        <?php foreach (['Inter', 'Arial', 'Georgia', 'Poppins', 'Source Sans 3'] as $font): ?>
                            <option <?= $resume['font_family'] === $font ? 'selected' : '' ?>><?= e($font) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="design-group">
                <div class="field"><label for="cvLanguage">Section language</label>
                    <select id="cvLanguage" data-resume-setting="language">
                        <option value="en" <?= $resume['language'] === 'en' ? 'selected' : '' ?>>English</option>
                        <option value="fr" <?= $resume['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                        <option value="es" <?= $resume['language'] === 'es' ? 'selected' : '' ?>>Español</option>
                    </select>
                    <span class="field-hint">Your content stays unchanged; section headings are translated.</span>
                </div>
            </div>

            <div class="design-group">
                <label>Content density</label>
                <div class="segmented-control">
                    <button type="button" data-density="compact">Compact</button>
                    <button class="active" type="button" data-density="comfortable">Comfortable</button>
                    <button type="button" data-density="spacious">Spacious</button>
                </div>
            </div>
        </div>
    </aside>

    <section class="preview-workspace">
        <div class="preview-toolbar">
            <div><span class="live-dot"></span><b>Live preview</b><small>A4 · changes update instantly</small></div>
            <div class="preview-controls">
                <button class="icon-btn" id="zoomOutButton" type="button" aria-label="Zoom out">−</button>
                <span id="zoomLabel">82%</span>
                <button class="icon-btn" id="zoomInButton" type="button" aria-label="Zoom in">+</button>
                <button class="icon-btn" id="fitButton" type="button" aria-label="Fit page" title="Fit page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/></svg>
                </button>
            </div>
        </div>
        <div class="preview-scroll" id="previewScroll">
            <div class="preview-scale" id="previewScale">
                <div id="resumePreview"></div>
            </div>
        </div>
        <button class="mobile-edit-button" type="button" data-toggle-panel="editor">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
            Edit content
        </button>
    </section>

    <aside class="assistant-panel" id="assistantPanel" aria-label="CV assistant">
        <div class="panel-mobile-header">
            <b>CV assistant</b>
            <button class="icon-btn" type="button" data-close-panel="assistant" aria-label="Close assistant">×</button>
        </div>
        <div class="assistant-heading">
            <div class="assistant-icon">✦</div>
            <div><h2>CV Assistant</h2><p>Private, practical guidance</p></div>
        </div>

        <div class="assistant-tabs" role="tablist">
            <button class="active" type="button" data-assistant-tab="assistant">Improve</button>
            <button type="button" data-assistant-tab="ats">ATS score</button>
            <button type="button" data-assistant-tab="job">Job match</button>
        </div>

        <div class="assistant-scroll">
            <section class="assistant-tab-view active" data-assistant-view="assistant">
                <div class="assistant-intro">
                    <span class="sparkle">✦</span>
                    <div><b>What would you like to improve?</b><p>Choose a focused action. You always review suggestions before applying them.</p></div>
                </div>

                <div class="assistant-actions-list">
                    <button type="button" data-smart-action="summary">
                        <span class="assistant-action-icon purple">≡</span>
                        <div><b>Write my summary</b><small>Create a focused professional introduction</small></div><i>›</i>
                    </button>
                    <button type="button" data-smart-action="bullet">
                        <span class="assistant-action-icon blue">↗</span>
                        <div><b>Improve a bullet</b><small>Turn a responsibility into an achievement</small></div><i>›</i>
                    </button>
                    <button type="button" data-smart-action="keywords">
                        <span class="assistant-action-icon green">#</span>
                        <div><b>Find missing keywords</b><small>Compare your CV with the target role</small></div><i>›</i>
                    </button>
                    <button type="button" data-smart-action="tips">
                        <span class="assistant-action-icon amber">✓</span>
                        <div><b>Review my content</b><small>Get the most useful next improvements</small></div><i>›</i>
                    </button>
                </div>

                <div class="assistant-result" id="assistantResult" hidden>
                    <div class="result-heading"><span>Suggestion</span><button type="button" id="dismissSuggestion">×</button></div>
                    <div id="assistantResultContent"></div>
                    <button class="btn btn-primary btn-small" id="applySuggestion" type="button" hidden>Use this suggestion</button>
                </div>

                <div class="voice-card">
                    <div><span>◉</span><div><b>Voice input</b><small>Place your cursor in a text field, then dictate.</small></div></div>
                    <button class="btn btn-secondary btn-small" id="voiceButton" type="button">Start</button>
                </div>
            </section>

            <section class="assistant-tab-view" data-assistant-view="ats">
                <div class="ats-score-card">
                    <div class="ats-ring" id="atsRing" style="--score:<?= (int) $resume['ats_score'] ?>"><strong id="atsScore"><?= (int) $resume['ats_score'] ?></strong><small>/100</small></div>
                    <div><span>ATS readiness</span><h3 id="atsGrade"><?= (int) $resume['ats_score'] >= 70 ? 'Strong foundation' : 'Ready to improve' ?></h3><p id="atsScoreMessage">Run a scan after adding your key content.</p></div>
                </div>
                <button class="btn btn-primary full-button" id="runAtsButton" type="button">Run ATS scan</button>
                <div class="ats-categories" id="atsCategories"></div>
                <div class="recommendation-list" id="atsRecommendations">
                    <div class="recommendation"><span>1</span><p>Add complete contact details, a focused summary, and achievement-based experience.</p></div>
                </div>
            </section>

            <section class="assistant-tab-view" data-assistant-view="job">
                <div class="job-match-intro">
                    <span>#</span>
                    <h3>Match a target role</h3>
                    <p>Paste the job description. We extract important terms and compare them with your CV locally.</p>
                </div>
                <div class="field">
                    <label for="jobDescription">Job description</label>
                    <textarea id="jobDescription" maxlength="15000" placeholder="Paste the responsibilities and requirements here…"><?= e($resume['job_description'] ?? '') ?></textarea>
                    <span class="field-hint"><span id="jobDescriptionCount">0</span> / 15,000 characters</span>
                </div>
                <button class="btn btn-primary full-button" id="matchJobButton" type="button">Analyze job match</button>
                <div class="keyword-result" id="keywordResult" hidden>
                    <div><h4>Matched keywords</h4><div class="keyword-cloud matched" id="matchedKeywords"></div></div>
                    <div><h4>Consider adding</h4><p>Only where they truthfully describe your work.</p><div class="keyword-cloud missing" id="missingKeywords"></div></div>
                </div>
            </section>
        </div>

        <div class="progress-reward">
            <div class="progress-ring-small" id="progressRingSmall" style="--progress:<?= (int) $resume['completion'] ?>"><strong id="progressSmallValue"><?= (int) $resume['completion'] ?>%</strong></div>
            <div><b id="progressLevel">Building foundation</b><span id="progressRewardText">Complete sections to reach Application Ready.</span></div>
        </div>
    </aside>
</main>

<div class="mobile-panel-overlay" id="mobilePanelOverlay"></div>

<div class="modal" id="summaryAssistantModal" role="dialog" aria-modal="true" aria-labelledby="summaryAssistantTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header"><div><p class="eyebrow">Smart writer</p><h2 id="summaryAssistantTitle">Shape your summary</h2></div><button class="icon-btn" type="button" data-modal-close="summaryAssistantModal">×</button></div>
        <div class="assistant-modal-form">
            <div class="field"><label for="summaryTargetRole">Target role</label><input id="summaryTargetRole" placeholder="e.g. Software Engineer"></div>
            <div class="field"><label for="summaryYears">Years of experience</label><input id="summaryYears" type="number" min="0" max="50" placeholder="e.g. 3"></div>
            <div class="field"><label for="summaryValue">Strongest value or result</label><textarea id="summaryValue" placeholder="e.g. building reliable web applications for growing teams"></textarea></div>
        </div>
        <div class="modal-actions"><button class="btn btn-secondary" type="button" data-modal-close="summaryAssistantModal">Cancel</button><button class="btn btn-primary" id="generateSummaryButton" type="button">Generate suggestion</button></div>
    </div>
</div>

<div class="modal" id="bulletAssistantModal" role="dialog" aria-modal="true" aria-labelledby="bulletAssistantTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header"><div><p class="eyebrow">Achievement writer</p><h2 id="bulletAssistantTitle">Improve a bullet</h2></div><button class="icon-btn" type="button" data-modal-close="bulletAssistantModal">×</button></div>
        <div class="assistant-modal-form">
            <div class="field"><label for="bulletSource">What did you do?</label><textarea id="bulletSource" placeholder="e.g. Responsible for customer support and daily sales"></textarea></div>
            <div class="field"><label for="bulletOutcome">What changed? <span class="muted">(optional)</span></label><input id="bulletOutcome" placeholder="e.g. 20% faster response time"></div>
        </div>
        <div class="modal-actions"><button class="btn btn-secondary" type="button" data-modal-close="bulletAssistantModal">Cancel</button><button class="btn btn-primary" id="generateBulletButton" type="button">Improve bullet</button></div>
    </div>
</div>

<script type="application/json" id="builderData"><?= json_encode($builderPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?></script>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('resume/renderer.js')) ?>" defer></script>
<script src="<?= e(asset('resume/builder.js')) ?>" defer></script>
</body>
</html>
