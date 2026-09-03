<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <?php View::partial('components/head_meta'); ?>
    <meta name="description" content="Create, improve, and download a professional CV with clear guidance and practical templates.">
    <title><?= e($title ?? 'Create a professional CV') ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('landing/landing.css')) ?>">
</head>
<body class="landing-page">
<header class="landing-nav">
    <div class="container landing-nav-inner">
        <?php View::partial('components/logo'); ?>
        <nav class="landing-links" aria-label="Landing page navigation">
            <a href="#product">Product</a>
            <a href="#workflow">How it works</a>
            <a href="#templates">Templates</a>
        </nav>
        <div class="landing-actions">
            <?php if (!empty($user)): ?>
                <a class="btn btn-primary" href="<?= e(base_url('/dashboard')) ?>">Open dashboard</a>
            <?php else: ?>
                <a class="login-link" href="<?= e(base_url('/login')) ?>">Sign in</a>
                <a class="btn btn-primary" href="<?= e(base_url('/register')) ?>">Create your CV</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <section class="hero" id="product">
        <div class="container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">A practical CV builder</p>
                <h1>Create a professional CV, without fighting the formatting.</h1>
                <p class="hero-lead">Write your experience clearly, see changes as you make them, and download a polished CV when you are ready to apply.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary hero-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/register')) ?>">
                        <?= Auth::check() ? 'Continue building' : 'Create your CV' ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                    <a class="btn btn-secondary" href="#templates">View templates</a>
                </div>
                <ul class="hero-points" aria-label="Product benefits">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        Saves as you work
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        ATS and job-match checks
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        Print-ready PDF output
                    </li>
                </ul>
            </div>

            <div class="product-window" aria-label="Preview of the <?= e(APP_NAME) ?> CV editing workspace">
                <div class="window-bar">
                    <span class="window-title">Marketing CV</span>
                    <span class="window-status"><i aria-hidden="true"></i> Saved</span>
                </div>
                <div class="product-grid">
                    <aside class="sample-sections" aria-label="CV sections">
                        <p>CV sections</p>
                        <span class="active">Personal details</span>
                        <span>Summary</span>
                        <span>Experience</span>
                        <span>Education</span>
                        <span>Skills</span>
                    </aside>
                    <section class="sample-paper" aria-label="Example CV preview">
                        <header>
                            <h2>JENNIFER DOE</h2>
                            <p>Marketing Manager</p>
                            <small>Accra, Ghana · jennifer@example.com · +233 24 000 0000</small>
                        </header>
                        <div class="sample-rule"></div>
                        <h3>Professional summary</h3>
                        <p>Marketing professional with experience planning campaigns, improving audience engagement, and coordinating collaborative teams.</p>
                        <h3>Experience</h3>
                        <div class="sample-role"><strong>Marketing Manager</strong><span>2019—Present</span></div>
                        <small>ABC Company</small>
                        <ul>
                            <li>Led integrated campaigns across three growth channels.</li>
                            <li>Improved qualified leads through clearer audience targeting.</li>
                        </ul>
                        <h3>Education</h3>
                        <div class="sample-role"><strong>BSc Marketing</strong><span>2018</span></div>
                        <small>University of Ghana</small>
                    </section>
                    <aside class="sample-guidance" aria-label="CV review summary">
                        <p>CV review</p>
                        <div class="review-item good">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                            <div><strong>Strong structure</strong><span>Key sections are complete.</span></div>
                        </div>
                        <div class="review-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 19.5V16l10-10 4 4-10 10H4Z"/><path d="m12.5 7.5 4 4"/></svg>
                            <div><strong>Improve one bullet</strong><span>Add a measurable result.</span></div>
                        </div>
                        <div class="review-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                            <div><strong>Job match</strong><span>Review missing keywords.</span></div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </section>

    <section class="capability-strip" id="features" aria-label="Available CV tools">
        <div class="container capability-grid">
            <div><strong>Live editing</strong><span>See the finished page while you write.</span></div>
            <div><strong>Practical feedback</strong><span>Check structure, wording, and job relevance.</span></div>
            <div><strong>Flexible templates</strong><span>Change the layout without losing your content.</span></div>
        </div>
    </section>

    <section class="process-section" id="workflow">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow">How it works</p>
                <h2>From first draft to finished CV in three clear steps.</h2>
                <p>The workspace keeps the process simple, even if this is the first CV you have created.</p>
            </div>
            <ol class="process-grid">
                <li>
                    <span class="step-number">1</span>
                    <div><h3>Choose a template</h3><p>Start with a professional layout suited to your experience and target role.</p></div>
                </li>
                <li>
                    <span class="step-number">2</span>
                    <div><h3>Add and improve your content</h3><p>Complete each section, review practical suggestions, and tailor it to the job.</p></div>
                </li>
                <li>
                    <span class="step-number">3</span>
                    <div><h3>Preview and download</h3><p>Check the final A4 layout, then print or save it as a PDF for your application.</p></div>
                </li>
            </ol>
        </div>
    </section>

    <section class="template-section" id="templates">
        <div class="container">
            <div class="template-heading">
                <div>
                    <p class="eyebrow">Professional templates</p>
                    <h2>Start with a layout that lets your experience lead.</h2>
                    <p>Each template uses the same CV content, so you can change your mind without starting again.</p>
                </div>
                <a class="btn btn-secondary" href="<?= e(Auth::check() ? base_url('/templates') : base_url('/register')) ?>"><?= Auth::check() ? 'Browse all templates' : 'Create an account' ?></a>
            </div>

            <div class="landing-template-grid">
                <?php foreach ([
                    ['modern', 'Modern Focus', 'Clear single-column hierarchy', '#4052b5'],
                    ['executive', 'Executive Edge', 'Structured layout for experienced professionals', '#16324f'],
                    ['minimal', 'Quiet Minimal', 'Generous spacing and understated typography', '#202124'],
                ] as [$key, $name, $description, $color]): ?>
                    <article class="landing-template-card">
                        <div class="template-preview-wrap">
                            <div class="template-sheet template-<?= e($key) ?>" style="--preview-accent:<?= e($color) ?>" aria-label="<?= e($name) ?> template preview">
                                <header><strong>ALEX MORGAN</strong><span>PRODUCT &amp; OPERATIONS LEAD</span></header>
                                <div class="template-contact-line"></div>
                                <h3>PROFILE</h3><i></i><i class="short"></i>
                                <h3>EXPERIENCE</h3><b></b><i></i><i></i><b></b><i class="short"></i>
                                <h3>EDUCATION</h3><b></b><i></i>
                            </div>
                        </div>
                        <div class="template-card-copy">
                            <h3><?= e($name) ?></h3>
                            <p><?= e($description) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="container final-cta-inner">
            <div>
                <p class="eyebrow">Ready when you are</p>
                <h2>Build a CV you can send with confidence.</h2>
                <p>Your content stays editable, and the layout stays professional.</p>
            </div>
            <a class="btn final-cta-button" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/register')) ?>"><?= Auth::check() ? 'Open your dashboard' : 'Create your CV' ?></a>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="container landing-footer-inner">
        <?php View::partial('components/logo'); ?>
        <p>© <?= date('Y') ?> <?= e(APP_NAME) ?>. Professional CVs, made simpler.</p>
        <a href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/login')) ?>"><?= Auth::check() ? 'Dashboard' : 'Sign in' ?></a>
    </div>
</footer>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
<script src="<?= e(asset('common/pwa.js')) ?>" defer></script>
</body>
</html>
