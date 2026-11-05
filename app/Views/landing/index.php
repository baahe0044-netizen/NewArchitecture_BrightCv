<?php
$signedIn = Auth::check();
$startUrl = $signedIn ? base_url('/dashboard') : base_url('/resume/builder');
$startLabel = $signedIn ? 'Open your dashboard' : 'Create your CV';
$faqs = [
    ['Is it free?', 'Yes. Writing your CV, choosing a template, and downloading it as a PDF cost nothing.'],
    ['Do I need an account?', 'Not to start. You can write your whole CV straight away; a free account is only needed when you download it, so your work is saved and you can come back to it.'],
    ['Will my CV get past applicant tracking systems?', 'The templates use clear headings and real text, and the built-in ATS check flags weak or missing content and compares your CV with a job description.'],
    ['Can I change the template later?', 'Any time. Your content stays exactly as it is when you switch templates, colours, or fonts.'],
    ['Can I add a photo?', 'Yes. Add one in Personal details and it appears on every template that has a picture.'],
];
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
        <nav class="landing-links" aria-label="Page sections">
            <a href="#how">How it works</a>
            <a href="#templates">Templates</a>
            <a href="#faq">FAQ</a>
        </nav>
        <div class="landing-actions">
            <?php View::partial('components/theme_toggle'); ?>
            <?php if ($signedIn): ?>
                <a class="btn btn-primary landing-cta" href="<?= e(base_url('/dashboard')) ?>">Dashboard</a>
            <?php else: ?>
                <a class="btn btn-secondary landing-login" href="<?= e(base_url('/login')) ?>">Log in</a>
                <a class="btn btn-primary landing-cta" href="<?= e(base_url('/resume/builder')) ?>">Create CV</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-copy">
                <h1>Build a professional CV in minutes</h1>
                <p class="hero-lead">Fill in your details, choose a template, and download your CV. No design skills needed.</p>
                <a class="btn btn-primary hero-button" href="<?= e($startUrl) ?>"><?= e($startLabel) ?></a>
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

    <section class="how" id="how" aria-labelledby="howTitle">
        <div class="container">
            <h2 id="howTitle">How it works</h2>
            <ol class="step-list">
                <li><span>1</span><h3>Add your details</h3><p>Work through short, guided sections, with examples and skill suggestions along the way.</p></li>
                <li><span>2</span><h3>Choose a template</h3><p>Pick a layout and colour that suit you. Switch whenever you like.</p></li>
                <li><span>3</span><h3>Download your CV</h3><p>Save a print-ready PDF and come back to edit it at any time.</p></li>
            </ol>
        </div>
    </section>

    <section class="features" aria-labelledby="featuresTitle">
        <div class="container">
            <h2 id="featuresTitle">Everything you need for a strong CV</h2>
            <ul class="feature-list">
                <li>
                    <span class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12s3.3-6 9-6 9 6 9 6-3.3 6-9 6-9-6-9-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></span>
                    <div><h3>Live preview</h3><p>See the finished page change as you type.</p></div>
                </li>
                <li>
                    <span class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5zM8 8h8M8 12h5"/><path d="m9 16 1.5 1.5L14 14"/></svg></span>
                    <div><h3>ATS check</h3><p>Find weak sections and missing keywords before you apply.</p></div>
                </li>
                <li>
                    <span class="feature-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 20h14"/></svg></span>
                    <div><h3>PDF download</h3><p>A clean A4 file, ready to send or print.</p></div>
                </li>
            </ul>
        </div>
    </section>

    <section class="templates-strip" id="templates" aria-labelledby="templatesTitle">
        <div class="container">
            <h2 id="templatesTitle">Templates recruiters can read at a glance</h2>
            <div class="template-row" aria-hidden="true">
                <?php foreach (['#2563EB', '#1D1D20', '#0F766E'] as $index => $color): ?>
                    <div class="mini-cv mini-cv-<?= $index + 1 ?>" style="--mini-accent:<?= e($color) ?>">
                        <b></b><small></small><i class="rule"></i><i></i><i></i><i class="short"></i><i></i><i class="short"></i>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="faq" id="faq" aria-labelledby="faqTitle">
        <div class="container faq-inner">
            <h2 id="faqTitle">Frequently asked questions</h2>
            <div class="faq-list">
                <?php foreach ($faqs as [$question, $answer]): ?>
                    <details>
                        <summary><?= e($question) ?></summary>
                        <p><?= e($answer) ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="container final-cta-inner">
            <h2>Ready to make your CV?</h2>
            <p>It only takes a few minutes to get started.</p>
            <a class="btn btn-primary hero-button" href="<?= e($startUrl) ?>"><?= e($startLabel) ?></a>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="container landing-footer-inner">
        <?php View::partial('components/logo'); ?>
        <nav class="footer-links" aria-label="Footer">
            <a href="#how">How it works</a>
            <a href="#faq">FAQ</a>
            <?php if (!$signedIn): ?><a href="<?= e(base_url('/login')) ?>">Log in</a><?php endif; ?>
        </nav>
        <p>© <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </div>
</footer>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
</body>
</html>
