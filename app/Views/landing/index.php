<?php
$signedIn = Auth::check();
$startUrl = $signedIn ? base_url('/dashboard') : base_url('/resume/builder');
$startLabel = $signedIn ? 'Open your dashboard' : 'Create your CV';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <meta name="description" content="Fill in your details, pick a template, and download a professional CV.">
    <title><?= e($title ?? 'Create a professional CV') ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('landing/landing.css')) ?>">
</head>
<body class="landing-page">
<header class="landing-nav">
    <div class="container landing-nav-inner">
        <?php View::partial('components/logo'); ?>
        <div class="landing-actions">
            <?php View::partial('components/theme_toggle'); ?>
            <?php if ($signedIn): ?>
                <a class="btn btn-primary" href="<?= e(base_url('/dashboard')) ?>">Dashboard</a>
            <?php else: ?>
                <a class="landing-login" href="<?= e(base_url('/login')) ?>">Log in</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-copy">
                <h1>Build a professional CV in minutes.</h1>
                <p class="hero-lead">Fill in your details, pick a template, and download. Free to start, no account needed.</p>
                <a class="btn btn-primary hero-button" href="<?= e($startUrl) ?>">
                    <?= e($startLabel) ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
                <?php if (!$signedIn): ?>
                    <p class="hero-note">Already have an account? <a href="<?= e(base_url('/login')) ?>">Log in</a></p>
                <?php endif; ?>
            </div>

            <div class="hero-sheet" aria-hidden="true">
                <div class="hero-sheet-head">
                    <span class="hero-sheet-photo">AM</span>
                    <div><b>AMA MENSAH</b><small>Marketing Manager</small></div>
                </div>
                <i class="rule"></i>
                <em>Summary</em><i></i><i class="short"></i>
                <em>Experience</em><i></i><i></i><i class="short"></i>
                <em>Skills</em>
                <div class="hero-sheet-chips"><span></span><span></span><span></span></div>
            </div>
        </div>
    </section>

    <section class="steps" aria-labelledby="stepsTitle">
        <div class="container">
            <h2 id="stepsTitle">How it works</h2>
            <ol class="step-list">
                <li><span>1</span><div><h3>Fill in your details</h3><p>Guided steps with examples and skill suggestions.</p></div></li>
                <li><span>2</span><div><h3>Pick a template</h3><p>Switch any time. Your content stays.</p></div></li>
                <li><span>3</span><div><h3>Download</h3><p>Get a print-ready PDF in one tap.</p></div></li>
            </ol>
        </div>
    </section>

    <section class="templates-strip" aria-labelledby="templatesTitle">
        <div class="container">
            <h2 id="templatesTitle">Clean, recruiter-friendly templates</h2>
            <div class="template-row" aria-hidden="true">
                <?php foreach (['#1D4ED8', '#0A1F44', '#0B0B0F'] as $index => $color): ?>
                    <div class="mini-cv mini-cv-<?= $index + 1 ?>" style="--mini-accent:<?= e($color) ?>">
                        <b></b><small></small><i class="rule"></i><i></i><i></i><i class="short"></i><i></i><i class="short"></i>
                    </div>
                <?php endforeach; ?>
            </div>
            <a class="btn btn-primary" href="<?= e($startUrl) ?>"><?= e($startLabel) ?></a>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="container landing-footer-inner">
        <?php View::partial('components/logo'); ?>
        <p>© <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </div>
</footer>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
</body>
</html>
