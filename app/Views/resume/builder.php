<?php
$builderPayload = [
    'resume' => $resume,
    'templates' => $templates,
    'endpoints' => [
        'save' => '/api/resumes/' . $resume['id'],
        'ats' => '/api/resumes/' . $resume['id'] . '/ats',
        'assistant' => '/api/resumes/' . $resume['id'] . '/assistant',
        'import' => '/api/resumes/' . $resume['id'] . '/import',
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
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
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
        <span class="save-dot"></span><span id="saveStatusText">Saved</span>
    </div>

    <div class="builder-actions">
        <?php if ($gamification !== null && (int) $gamification['xp_today'] > 0): ?>
            <span class="chip chip-gold builder-xp-chip">+<?= (int) $gamification['xp_today'] ?> XP today</span>
        <?php endif; ?>
        <!--
            These four sit inline in the topbar on a wide screen and collapse
            into the "More actions" menu on a phone. They stay one set of
            buttons so history, import, and backup behave identically on
            every device.
        -->
        <div class="builder-tools" id="builderTools">
            <button class="icon-btn" id="undoButton" type="button" title="Undo (Ctrl+Z)" aria-label="Undo" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 7 4 12l5 5M5 12h9a5 5 0 0 1 5 5v1"/></svg>
                <span class="tool-label">Undo</span>
            </button>
            <button class="icon-btn" id="redoButton" type="button" title="Redo (Ctrl+Shift+Z)" aria-label="Redo" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 7 5 5-5 5M19 12h-9a5 5 0 0 0-5 5v1"/></svg>
                <span class="tool-label">Redo</span>
            </button>
            <button class="btn btn-secondary builder-secondary-action" id="importButton" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 20h14"/></svg>
                Import CV
            </button>
            <button class="btn btn-secondary builder-secondary-action" id="exportJsonButton" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 15V3M7 8l5-5 5 5"/><path d="M5 20h14"/></svg>
                Backup
            </button>
        </div>
        <button class="icon-btn builder-more-button" id="builderMoreButton" type="button" data-builder-more aria-controls="builderTools" aria-expanded="false" aria-label="More actions">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="12" cy="19" r="1.8"/></svg>
        </button>
        <button class="btn btn-primary" id="printButton" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg>
            Print / PDF
        </button>
        <button class="icon-btn builder-mobile-panel" type="button" data-toggle-panel="assistant" aria-controls="assistantPanel" aria-expanded="false" aria-label="Open CV review">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5zM8 8h8M8 12h5M8 16h4"/></svg>
        </button>
    </div>
</header>

<main class="builder-layout">
    <aside class="editor-panel" id="editorPanel" aria-label="CV editor">
        <div class="panel-mobile-header">
            <b>Edit CV</b>
            <button class="icon-btn" type="button" data-close-panel="editor" aria-label="Close editor"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="editor-mode-tabs" role="tablist">
            <button class="active" type="button" role="tab" data-editor-mode="content" aria-controls="editorContentMode" aria-selected="true">Content</button>
            <button type="button" role="tab" data-editor-mode="design" aria-controls="editorDesignMode" aria-selected="false">Design</button>
        </div>

        <div class="editor-content-mode" id="editorContentMode">
            <!--
                Progress and the section switcher stay pinned so the form itself
                is the first thing in view. They used to scroll with the content,
                which pushed every field ~400px down the panel.
            -->
            <div class="editor-sticky">
                <?php if ($gamification !== null): $level = $gamification['level']; ?>
                    <div class="stage-rail-level">
                        <div class="stage-rail-level-row">
                            <span class="level-number level-number-small"><?= (int) $level['level'] ?></span>
                            <div>
                                <p class="stage-rail-level-name"><?= e($level['name']) ?></p>
                                <p class="stage-rail-level-xp"><?= number_format((int) $gamification['xp']) ?> XP</p>
                            </div>
                        </div>
                        <div class="stage-rail-level-bar"><div style="width:<?= (int) $level['progress_percent'] ?>%"></div></div>
                        <p class="stage-rail-level-next"><?= number_format((int) $level['xp_to_next']) ?> XP to <?= e($level['next_name']) ?></p>
                    </div>
                <?php endif; ?>

                <div class="completion-summary">
                    <div class="completion-copy"><span>CV progress</span><b id="completionPercent"><?= (int) $resume['completion'] ?>%</b></div>
                    <div class="completion-bar"><i id="completionBar" style="width:<?= (int) $resume['completion'] ?>%"></i></div>
                    <small id="completionHint" class="sr-only">Complete the highlighted sections to strengthen your CV.</small>
                </div>

                <nav class="section-steps" id="sectionNav" aria-label="CV sections">
                    <?php
                    // The same weights ResumeService::completion() already scores a
                    // CV against, shown up front rather than invented for display --
                    // filling this section is worth exactly this much of the total.
                    $sectionWeights = [
                        'personal' => 20, 'summary' => 15, 'experience' => 25,
                        'education' => 15, 'skills' => 15, 'projects' => 10, 'extras' => null,
                    ];
                    $sectionLabels = [
                        'personal' => 'Personal', 'summary' => 'Summary', 'experience' => 'Experience',
                        'education' => 'Education', 'skills' => 'Skills', 'projects' => 'Projects', 'extras' => 'More',
                    ];
                    ?>
                    <?php foreach ($sectionLabels as $key => $label): ?>
                        <button class="<?= $key === 'personal' ? 'active' : '' ?>" type="button" data-section="<?= e($key) ?>">
                            <i data-section-check="<?= e($key) ?>">○</i>
                            <b><?= e($label) ?></b>
                            <?php if ($sectionWeights[$key] !== null): ?><span class="section-xp">+<?= (int) $sectionWeights[$key] ?></span><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </nav>
            </div>

            <section class="section-editor" id="sectionEditor" aria-live="polite"></section>

            <div class="step-footer">
                <button class="btn btn-secondary" type="button" data-step-prev>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    Back
                </button>
                <span class="step-count" id="stepCount"></span>
                <button class="btn btn-primary" type="button" data-step-next>
                    Next
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>

        <div class="editor-design-mode" id="editorDesignMode" hidden>
            <div class="design-heading"><p class="eyebrow">Visual style</p><h2>Choose the presentation.</h2><p>Your content stays intact when you change these options.</p></div>

            <div class="design-group">
                <p class="control-label">Template</p>
                <div class="mini-template-grid">
                    <?php foreach ($templates as $template): ?>
                        <button class="mini-template <?= $resume['template_key'] === $template['template_key'] ? 'active' : '' ?>" type="button" data-template-key="<?= e($template['template_key']) ?>" aria-pressed="<?= $resume['template_key'] === $template['template_key'] ? 'true' : 'false' ?>" title="<?= e($template['name']) ?>">
                            <span class="mini-sheet mini-<?= e($template['template_key']) ?>" style="--mini-accent:<?= e($template['color']) ?>"><i></i><i></i><b></b><i></i><i></i></span>
                            <small><?= e($template['name']) ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="design-group">
                <p class="control-label">Page layout</p>
                <div class="segmented-control" role="group" aria-label="Page layout">
                    <button type="button" data-layout="stacked" aria-pressed="false">Single column</button>
                    <button type="button" data-layout="sidebar" aria-pressed="false">Two column</button>
                </div>
                <p class="field-hint">Single column runs every section down the full width of the page, which most employers and applicant tracking systems read best.</p>
            </div>

            <div class="design-group">
                <p class="control-label">Section order</p>
                <div class="segmented-control" role="group" aria-label="Section order">
                    <button type="button" data-section-order="standard" aria-pressed="false">Experience first</button>
                    <button type="button" data-section-order="skills_first" aria-pressed="false">Skills first</button>
                </div>
                <p class="field-hint">Skills first suits technical roles, career changers, and new graduates.</p>
            </div>

            <div class="design-group">
                <p class="control-label">Accent colour</p>
                <div class="color-options">
                    <?php foreach (['#5b4df7', '#16324f', '#087f5b', '#e25241', '#b16a00', '#202124', '#7b2fbe', '#075985'] as $color): ?>
                        <button type="button" data-accent-color="<?= e($color) ?>" style="--swatch:<?= e($color) ?>" aria-label="Use <?= e($color) ?>" aria-pressed="false"></button>
                    <?php endforeach; ?>
                    <label class="custom-color" title="Custom colour"><span class="sr-only">Choose a custom colour</span><input id="customColor" type="color" value="<?= e($resume['accent_color']) ?>"><span aria-hidden="true">+</span></label>
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
                <p class="control-label">Content density</p>
                <div class="segmented-control">
                    <button type="button" data-density="compact" aria-pressed="false">Compact</button>
                    <button class="active" type="button" data-density="comfortable" aria-pressed="true">Comfortable</button>
                    <button type="button" data-density="spacious" aria-pressed="false">Spacious</button>
                </div>
            </div>
        </div>
    </aside>

    <section class="preview-workspace">
        <div class="preview-toolbar">
            <div><span class="live-dot"></span><b>Live preview</b><small>A4 · changes update instantly</small></div>
            <div class="robot-score-meter" title="How an applicant tracking system is likely to read this CV">
                <span>Robot score</span>
                <span class="robot-score-track"><span id="toolbarAtsMeter" style="width:<?= (int) $resume['ats_score'] ?>%"></span></span>
                <b id="toolbarAtsScore"><?= (int) $resume['ats_score'] ?></b>
            </div>
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
        <button class="mobile-edit-button" type="button" data-toggle-panel="editor" aria-controls="editorPanel" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
            Edit content
        </button>
    </section>

    <aside class="assistant-panel" id="assistantPanel" aria-label="CV assistant">
        <div class="panel-mobile-header">
            <b>CV review</b>
            <button class="icon-btn" type="button" data-close-panel="assistant" aria-label="Close CV review"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="assistant-heading">
            <div class="assistant-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5zM8 8h8M8 12h5"/><path d="m9 16 1.5 1.5L14 14"/></svg></div>
            <div><h2>CV review</h2><p>Private, practical guidance</p></div>
        </div>

        <div class="assistant-tabs" role="tablist">
            <button class="active" type="button" role="tab" data-assistant-tab="assistant" aria-selected="true">Improve</button>
            <button type="button" role="tab" data-assistant-tab="ats" aria-selected="false">ATS score</button>
            <button type="button" role="tab" data-assistant-tab="job" aria-selected="false">Job match</button>
        </div>

        <div class="assistant-scroll">
            <section class="assistant-tab-view active" data-assistant-view="assistant">
                <div class="assistant-intro">
                    <span class="sparkle" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><path d="M12 11v5M12 8v.2"/></svg></span>
                    <div><b>What would you like to improve?</b><p>Choose a focused action. You always review suggestions before applying them.</p></div>
                </div>

                <div class="assistant-actions-list">
                    <button type="button" data-smart-action="summary">
                        <span class="assistant-action-icon purple" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 5h14M5 9h14M5 13h9M5 17h7"/></svg></span>
                        <div><b>Write my summary</b><small>Create a focused professional introduction</small></div><i aria-hidden="true">›</i>
                    </button>
                    <button type="button" data-smart-action="bullet">
                        <span class="assistant-action-icon blue" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19.5V16l10-10 4 4-10 10H4Z"/><path d="m12.5 7.5 4 4"/></svg></span>
                        <div><b>Improve a bullet</b><small>Turn a responsibility into an achievement</small></div><i aria-hidden="true">›</i>
                    </button>
                    <button type="button" data-smart-action="keywords">
                        <span class="assistant-action-icon green" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></span>
                        <div><b>Find missing keywords</b><small>Compare your CV with the target role</small></div><i aria-hidden="true">›</i>
                    </button>
                    <button type="button" data-smart-action="tips">
                        <span class="assistant-action-icon amber" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5zM8 8h8M8 12h5M8 16h7"/></svg></span>
                        <div><b>Review my content</b><small>Get the most useful next improvements</small></div><i aria-hidden="true">›</i>
                    </button>
                </div>

                <div class="assistant-result" id="assistantResult" hidden>
                    <div class="result-heading"><span>Suggestion</span><button type="button" id="dismissSuggestion" aria-label="Dismiss suggestion">×</button></div>
                    <div id="assistantResultContent"></div>
                    <button class="btn btn-primary btn-small" id="applySuggestion" type="button" hidden>Use this suggestion</button>
                </div>

                <div class="voice-card">
                    <div><span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3M9 21h6"/></svg></span><div><b>Voice input</b><small>Place your cursor in a text field, then dictate.</small></div></div>
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
                    <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></span>
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
<?php View::partial('components/bottom_nav', ['active' => 'builder']); ?>

<nav class="builder-mobile-switch" aria-label="Builder view">
    <button type="button" data-toggle-panel="editor" aria-controls="editorPanel" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19.5V16l10-10 4 4-10 10H4Z"/><path d="m12.5 7.5 4 4"/></svg>
        Edit
    </button>
    <button class="active" type="button" data-close-panel="preview" aria-current="page">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12s3.3-6 9-6 9 6 9 6-3.3 6-9 6-9-6-9-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
        Preview
    </button>
    <button type="button" data-toggle-panel="assistant" aria-controls="assistantPanel" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h5"/><path d="m9 16 1.5 1.5L14 14"/></svg>
        Review
    </button>
</nav>

<div class="mobile-panel-overlay" id="mobilePanelOverlay"></div>

<div class="modal" id="importCvModal" role="dialog" aria-modal="true" aria-labelledby="importCvTitle" aria-hidden="true">
    <div class="modal-dialog import-dialog">
        <div class="modal-header">
            <div><p class="eyebrow">Import</p><h2 id="importCvTitle">Bring in a CV you already have</h2></div>
            <button class="icon-btn" type="button" data-modal-close="importCvModal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>

        <div class="import-body">
            <div class="import-tabs" role="tablist">
                <button class="active" type="button" role="tab" data-import-tab="file" aria-selected="true">Upload a file</button>
                <button type="button" role="tab" data-import-tab="text" aria-selected="false">Paste the text</button>
            </div>

            <section class="import-tab-view active" data-import-view="file">
                <label class="import-drop" id="importDrop" for="importFile">
                    <span class="import-drop-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></svg></span>
                    <b>Choose a file or drop it here</b>
                    <small>PDF, Word (.docx), plain text, or a BrightCV backup. Up to 5 MB.</small>
                    <input class="sr-only" id="importFile" type="file" accept=".pdf,.docx,.txt,.md,.json,application/pdf,application/json,text/plain">
                </label>
                <p class="import-file-name" id="importFileName" hidden></p>
            </section>

            <section class="import-tab-view" data-import-view="text">
                <div class="field">
                    <label for="importText">CV text</label>
                    <textarea id="importText" rows="10" maxlength="30000" placeholder="Open your CV, select everything, copy, and paste it here…"></textarea>
                    <span class="field-hint">Use this for scanned PDFs, or any file the reader cannot open.</span>
                </div>
            </section>

            <div class="import-result" id="importResult" hidden>
                <div class="import-result-head">
                    <b>Here is what was found</b>
                    <span id="importSource"></span>
                </div>
                <dl class="import-detected" id="importDetected"></dl>
                <p class="import-skipped" id="importSkipped" role="note" hidden></p>
                <p class="import-warning" role="note">Applying this replaces the content of <strong id="importTargetName">this CV</strong>. Your design settings stay as they are, and undo (Ctrl+Z) reverses it.</p>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" type="button" data-modal-close="importCvModal">Cancel</button>
            <button class="btn btn-secondary" id="readImportButton" type="button">Read CV</button>
            <button class="btn btn-primary" id="applyImportButton" type="button" hidden>Apply to my CV</button>
        </div>
    </div>
</div>

<div class="modal" id="summaryAssistantModal" role="dialog" aria-modal="true" aria-labelledby="summaryAssistantTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header"><div><p class="eyebrow">Summary writer</p><h2 id="summaryAssistantTitle">Shape your summary</h2></div><button class="icon-btn" type="button" data-modal-close="summaryAssistantModal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
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
        <div class="modal-header"><div><p class="eyebrow">Achievement writer</p><h2 id="bulletAssistantTitle">Improve a bullet</h2></div><button class="icon-btn" type="button" data-modal-close="bulletAssistantModal" aria-label="Close"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></div>
        <div class="assistant-modal-form">
            <div class="field"><label for="bulletSource">What did you do?</label><textarea id="bulletSource" placeholder="e.g. Responsible for customer support and daily sales"></textarea></div>
            <div class="field"><label for="bulletOutcome">What changed? <span class="muted">(optional)</span></label><input id="bulletOutcome" placeholder="e.g. 20% faster response time"></div>
        </div>
        <div class="modal-actions"><button class="btn btn-secondary" type="button" data-modal-close="bulletAssistantModal">Cancel</button><button class="btn btn-primary" id="generateBulletButton" type="button">Improve bullet</button></div>
    </div>
</div>

<script type="application/json" id="builderData"><?= json_encode($builderPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?></script>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
<script src="<?= e(asset('resume/renderer.js')) ?>" defer></script>
<script src="<?= e(asset('resume/builder.js')) ?>" defer></script>
</body>
</html>
