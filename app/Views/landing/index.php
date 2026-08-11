<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php View::partial('components/theme_init'); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Build a polished, ATS-ready CV with live guidance, job matching, and professional templates.">
    <meta name="app-url" content="<?= e(BASE_URL) ?>">
    <title><?= e($title ?? 'Build a better CV') ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(asset('common/app.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('landing/landing.css')) ?>">
</head>
<body class="landing-page">
<header class="landing-nav">
    <div class="container landing-nav-inner">
        <?php View::partial('components/logo'); ?>
        <nav class="landing-links" aria-label="Landing navigation">
            <a href="#features">Features</a>
            <a href="#workflow">How it works</a>
            <a href="#templates">Templates</a>
        </nav>
        <div class="landing-actions">
            <?php View::partial('components/theme_toggle'); ?>
            <?php if (!empty($user)): ?>
                <a class="btn btn-primary" href="<?= e(base_url('/dashboard')) ?>">Open dashboard</a>
            <?php else: ?>
                <a class="login-link" href="<?= e(base_url('/login')) ?>">Sign in</a>
                <a class="btn btn-primary" href="<?= e(base_url('/register')) ?>">Build my CV</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-glow hero-glow-one"></div>
        <div class="hero-glow hero-glow-two"></div>
        <div class="container hero-grid">
            <div class="hero-copy">
                <div class="hero-badge">
                    <span>✦</span>
                    Your next opportunity starts with a stronger CV
                </div>
                <h1>Build the CV recruiters <em>want</em> to read.</h1>
                <p class="hero-lead">Create a polished, ATS-ready CV with live guidance, job-specific keywords, and smart writing support—all in one focused workspace.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary hero-primary" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/register')) ?>">
                        Start building free
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                    <a class="btn btn-secondary" href="#workflow">See how it works</a>
                </div>
                <div class="hero-trust">
                    <span><b>✓</b> No credit card</span>
                    <span><b>✓</b> Private by design</span>
                    <span><b>✓</b> Export to PDF</span>
                </div>
            </div>

            <div class="product-stage" aria-label="Preview of the BrightCV CV builder">
                <div class="product-window">
                    <div class="window-bar">
                        <div class="window-brand"><span>★</span> BrightCV</div>
                        <span class="window-title">CV Generator</span>
                        <div class="window-actions"><i></i><i></i><i></i></div>
                    </div>
                    <div class="product-grid">
                        <aside class="demo-tools">
                            <span class="demo-tool active">▤ <b>Resume</b></span>
                            <span class="demo-tool">✦ <b>Smart Writer</b></span>
                            <span class="demo-tool">◉ <b>Style</b></span>
                            <div class="demo-colors"><i></i><i></i><i></i><i></i></div>
                            <span class="demo-tool checked">✓ <b>Content check</b></span>
                            <span class="demo-tool checked">✓ <b>Keywords</b></span>
                            <span class="demo-tool checked">✓ <b>ATS scan</b></span>
                        </aside>
                        <section class="demo-paper">
                            <h2>JENNIFER DOE</h2>
                            <div class="demo-contact">jennifer@example.com &nbsp; · &nbsp; +233 24 000 0000 &nbsp; · &nbsp; Accra</div>
                            <div class="demo-rule"></div>
                            <h3>PROFESSIONAL SUMMARY</h3>
                            <p>Results-driven marketing professional with experience building campaigns that grow reach, engagement, and revenue.</p>
                            <h3>EXPERIENCE</h3>
                            <div class="demo-row"><b>Marketing Manager</b><span>2019 — Present</span></div>
                            <small>ABC Company</small>
                            <ul>
                                <li>Led integrated campaigns across three growth channels</li>
                                <li>Improved qualified leads by 32% in one year</li>
                                <li>Managed a collaborative team of five</li>
                            </ul>
                            <h3>EDUCATION</h3>
                            <div class="demo-row"><b>Bachelor of Science in Marketing</b><span>2018</span></div>
                            <small>University of Ghana</small>
                            <h3>SKILLS</h3>
                            <div class="demo-skills"><span>Digital Marketing</span><span>SEO</span><span>Analytics</span><span>Content Strategy</span></div>
                        </section>
                        <aside class="demo-assistant">
                            <h3>Optimize with ease</h3>
                            <div class="assistant-card purple"><i>✦</i><div><b>Smart suggestions</b><small>Sharper summary and bullet ideas</small></div></div>
                            <div class="assistant-card green"><i>✓</i><div><b>ATS feedback</b><small>Real-time readability checks</small></div></div>
                            <div class="score-demo">
                                <div class="score-ring">90</div>
                                <div><b>Great foundation</b><small>2 improvements left</small></div>
                            </div>
                            <div class="assistant-card blue"><i>⌁</i><div><b>Job match</b><small>Keywords for your target role</small></div></div>
                        </aside>
                    </div>
                </div>
                <div class="floating-card floating-score"><span>ATS score</span><strong>90<small>/100</small></strong></div>
                <div class="floating-card floating-save"><i>✓</i><div><strong>Changes saved</strong><span>Your work is protected</span></div></div>
            </div>
        </div>
    </section>

    <section class="proof-strip">
        <div class="container proof-grid">
            <div><strong>6</strong><span>professional layouts</span></div>
            <div><strong>3</strong><span>CV languages</span></div>
            <div><strong>Live</strong><span>ATS and job-match feedback</span></div>
            <div><strong>Auto</strong><span>secure saving and version history</span></div>
        </div>
    </section>

    <section class="feature-section" id="features">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow">Everything in one place</p>
                <h2>Less formatting. More career impact.</h2>
                <p>BrightCV keeps the technology quiet so you can focus on the story your CV needs to tell.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card feature-card-wide">
                    <span class="feature-icon purple">✦</span>
                    <h3>Smart writing that sounds like you</h3>
                    <p>Turn responsibilities into stronger achievement bullets, shape a focused summary, and receive practical suggestions without sending private CV data to a third party.</p>
                    <div class="suggestion-demo">
                        <span>Before</span>
                        <p>Responsible for helping customers and managing sales.</p>
                        <span>Improved</span>
                        <p><b>Improved</b> customer support and coordinated daily sales activities, strengthening service consistency.</p>
                    </div>
                </article>
                <article class="feature-card">
                    <span class="feature-icon green">✓</span>
                    <h3>ATS health check</h3>
                    <p>See missing sections, weak bullets, readability issues, and practical next steps before you apply.</p>
                    <div class="mini-meter"><i style="width:86%"></i></div>
                    <small>Strong · 86/100</small>
                </article>
                <article class="feature-card">
                    <span class="feature-icon blue">⌁</span>
                    <h3>Job-specific keywords</h3>
                    <p>Paste a job description to identify matched and missing terms, then add only the ones that honestly fit.</p>
                    <div class="keyword-pills"><span>project delivery</span><span>SQL</span><span>stakeholders</span></div>
                </article>
                <article class="feature-card">
                    <span class="feature-icon amber">◉</span>
                    <h3>Your style, kept professional</h3>
                    <p>Switch templates, colours, typefaces, spacing, and translated section headings without breaking the layout.</p>
                    <div class="palette-row"><i></i><i></i><i></i><i></i><i></i></div>
                </article>
                <article class="feature-card feature-card-wide">
                    <span class="feature-icon rose">↗</span>
                    <h3>Build once, tailor often</h3>
                    <p>Duplicate a strong CV for a new role, maintain version history, import or export JSON backups, and produce a clean A4 PDF whenever you are ready.</p>
                    <div class="workflow-chips"><span>Autosave</span><b>→</b><span>Tailor</span><b>→</b><span>ATS scan</span><b>→</b><span>PDF</span></div>
                </article>
            </div>
        </div>
    </section>

    <section class="workflow-section" id="workflow">
        <div class="container workflow-grid">
            <div class="workflow-copy">
                <p class="eyebrow">A calmer workflow</p>
                <h2>From blank page to application-ready.</h2>
                <p>Each step gives you a clear action, a live preview, and feedback that explains what to improve next.</p>
                <ol class="steps">
                    <li><b>Choose a direction</b><span>Pick a role and one of six professional templates.</span></li>
                    <li><b>Add your evidence</b><span>Build experience, education, skills, projects, and certifications.</span></li>
                    <li><b>Tailor and check</b><span>Match a job description and improve ATS readiness.</span></li>
                    <li><b>Export with confidence</b><span>Print a crisp A4 PDF or keep a portable JSON backup.</span></li>
                </ol>
            </div>
            <div class="workflow-visual">
                <div class="mini-cv">
                    <div class="mini-cv-head"><span></span><div><b>YOUR NAME</b><i>Target role</i></div></div>
                    <hr>
                    <b class="mini-title">PROFILE</b><p></p><p class="short"></p>
                    <b class="mini-title">EXPERIENCE</b><div class="mini-line"></div><p></p><p></p>
                    <b class="mini-title">SKILLS</b><div class="mini-tags"><i></i><i></i><i></i><i></i></div>
                </div>
                <div class="workflow-note note-one"><b>✓ 100% complete</b><span>Every key section covered</span></div>
                <div class="workflow-note note-two"><b>✦ 5 strong bullets</b><span>Clear action and impact</span></div>
            </div>
        </div>
    </section>

    <section class="template-section" id="templates">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow">Designed for real applications</p>
                <h2>A professional first impression, every time.</h2>
                <p>All layouts remain readable, printable, responsive, and easy for applicant tracking systems to parse.</p>
            </div>
            <div class="landing-template-grid">
                <?php foreach ([
                    ['modern', 'Modern Focus', '#6757e8'],
                    ['executive', 'Executive Edge', '#17334f'],
                    ['minimal', 'Quiet Minimal', '#222222'],
                ] as [$key, $name, $color]): ?>
                    <article class="landing-template-card">
                        <div class="template-sheet template-<?= e($key) ?>" style="--preview-accent:<?= e($color) ?>">
                            <div class="preview-name">ALEX MORGAN</div><span>PRODUCT DESIGNER</span><hr>
                            <b>PROFILE</b><i></i><i></i>
                            <b>EXPERIENCE</b><i></i><i></i><i></i>
                            <b>EDUCATION</b><i></i>
                        </div>
                        <div><h3><?= e($name) ?></h3><span>ATS-friendly · A4 ready</span></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="final-cta">
        <div class="container final-cta-card">
            <div>
                <p class="eyebrow">Make the next application count</p>
                <h2>Your experience deserves a better CV.</h2>
                <p>Start with a professional structure and improve it one clear suggestion at a time.</p>
            </div>
            <a class="btn final-cta-button" href="<?= e(Auth::check() ? base_url('/dashboard') : base_url('/register')) ?>">Build my CV →</a>
        </div>
    </section>
</main>

<footer class="landing-footer">
    <div class="container">
        <?php View::partial('components/logo'); ?>
        <p>© <?= date('Y') ?> BrightCV. Built to help your work stand out.</p>
        <div><a href="<?= e(base_url('/login')) ?>">Sign in</a><a href="#features">Features</a></div>
    </div>
</footer>
<script src="<?= e(asset('common/app.js')) ?>" defer></script>
</body>
</html>
