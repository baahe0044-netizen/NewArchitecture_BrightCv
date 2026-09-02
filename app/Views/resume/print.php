<!doctype html>
<html lang="<?= e($resume['language'] ?? 'en') ?>">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <title><?= e($resume['name']) ?> · Print preview</title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('resume/preview.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('resume/print.css')) ?>">
</head>
<body class="print-page">
<header class="print-toolbar">
    <div class="print-document-name">
        <?php View::partial('components/logo'); ?>
        <span aria-hidden="true"></span>
        <div><b><?= e($resume['name']) ?></b><small>A4 print preview</small></div>
    </div>
    <div class="print-actions">
        <a class="btn btn-secondary" href="<?= e(base_url('/resume/builder/' . $resume['id'])) ?>">Continue editing</a>
        <button class="btn btn-primary" id="printNowButton" type="button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7z"/></svg>
            Print or save PDF
        </button>
    </div>
</header>
<div class="print-tip" role="note"><b>For a clean PDF:</b> choose A4 paper, set margins to none, use 100% scale, and enable background graphics.</div>
<main class="print-canvas">
    <div class="print-sheet-wrap" id="printSheetWrap"><div id="printResume"></div></div>
</main>
<noscript><p class="print-noscript">JavaScript is required to prepare this print preview.</p></noscript>
<script type="application/json" id="printData"><?= json_encode([
    'resume' => $resume,
    'exportEndpoint' => '/api/resumes/' . $resume['id'] . '/export',
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?></script>
<script src="<?= e(asset('common/app.js')) ?>"></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
<script src="<?= e(asset('resume/renderer.js')) ?>"></script>
<script src="<?= e(asset('resume/print.js')) ?>"></script>
</body>
</html>
