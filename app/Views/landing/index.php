<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bright CV — Build a Resume That Gets You Hired</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;1,700&family=DM+Sans:wght@300;400;500&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/landing/landing.css">
</head>

<body>

    <!-- ─── Navigation ─── -->
    <nav id="nav">
        <a href="#" class="nav-logo">
            <span class="nav-logo-dot"></span>
            Bright CV
        </a>
        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#templates">Templates</a></li>
            <li><a href="#testimonials">Stories</a></li>
            <li><a href="<?= BASE_URL ?>/resume/builder" class="nav-cta">Build Your CV</a></li>
        </ul>
    </nav>

    <!-- ─── Hero ─── -->
    <section class="hero">
        <div class="hero-bg-grid"></div>
        <div class="hero-glow"></div>
        <div class="hero-glow-2"></div>
        <div class="hero-inner">
            <div class="hero-text reveal">
                <p class="hero-eyebrow">Professional Resume Builder</p>
                <h1 class="hero-title">
                    Your career deserves a<br>
                    <em>remarkable</em> first impression
                </h1>
                <p class="hero-desc">
                    Build a polished, ATS-optimized resume in minutes. Choose from expertly crafted templates, fill in your details, and land more interviews.
                </p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/dashboard" class="btn-primary">
                        Start Building Free <i class="fas fa-arrow-right" style="font-size:13px"></i>
                    </a>
                    <a href="#templates" class="btn-ghost">
                        <i class="fas fa-th-large"></i> Browse Templates
                    </a>
                </div>
                <div class="hero-trust">
                    <div class="hero-trust-avatars">
                        <span>J</span><span>M</span><span>A</span><span>K</span>
                    </div>
                    <p class="hero-trust-text">
                        Trusted by <strong>12,000+</strong> professionals worldwide
                    </p>
                </div>
            </div>

            <div class="hero-visual reveal" style="transition-delay:0.15s">
                <div class="resume-mockup">
                    <div class="mockup-bar">
                        <div class="mockup-dot" style="background:#FF5F57"></div>
                        <div class="mockup-dot" style="background:#FEBC2E"></div>
                        <div class="mockup-dot" style="background:#28C840"></div>
                        <span class="mockup-bar-title">resume_preview.pdf</span>
                    </div>
                    <div class="resume-preview">
                        <div class="rp-sidebar">
                            <div class="rp-avatar"><i class="fas fa-user"></i></div>
                            <div class="rp-bar gold"></div>
                            <div class="rp-bar short"></div>
                            <div class="rp-label">Contact</div>
                            <div class="rp-bar"></div>
                            <div class="rp-bar short"></div>
                            <div class="rp-bar"></div>
                            <div class="rp-label">Skills</div>
                            <div class="rp-bar gold"></div>
                            <div class="rp-bar short"></div>
                            <div class="rp-bar gold" style="width:70%"></div>
                        </div>
                        <div class="rp-main">
                            <div class="rp-name">Alexandra Chen</div>
                            <div class="rp-role">Senior Product Designer</div>
                            <div class="rp-section-title">Work Experience</div>
                            <div class="rp-exp-item">
                                <div class="rp-exp-title">Lead Product Designer</div>
                                <div class="rp-exp-co">TechCorp Inc · 2021 – Present</div>
                                <div class="rp-lines">
                                    <div class="rp-line"></div>
                                    <div class="rp-line short"></div>
                                    <div class="rp-line shorter"></div>
                                </div>
                            </div>
                            <div class="rp-exp-item">
                                <div class="rp-exp-title">UX Designer</div>
                                <div class="rp-exp-co">Startup Studio · 2019 – 2021</div>
                                <div class="rp-lines">
                                    <div class="rp-line"></div>
                                    <div class="rp-line shorter"></div>
                                </div>
                            </div>
                            <div class="rp-section-title" style="margin-top:14px">Education</div>
                            <div class="rp-exp-item">
                                <div class="rp-exp-title">B.Sc. Design Technology</div>
                                <div class="rp-exp-co">University of Ghana · 2019</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="floating-badge badge-1">
                    <div class="badge-icon green"><i class="fas fa-check"></i></div>
                    <span>ATS Score: <strong style="color:#10b981">94%</strong></span>
                </div>
                <div class="floating-badge badge-2">
                    <div class="badge-icon gold"><i class="fas fa-bolt"></i></div>
                    <span>Ready in <strong style="color:var(--gold)">3 min</strong></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Stats ─── -->
    <div class="stats-section reveal">
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-num">12K+</div>
                <div class="stat-label">Resumes created worldwide</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">94%</div>
                <div class="stat-label">Average ATS compatibility score</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">3×</div>
                <div class="stat-label">More interviews reported by users</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">15+</div>
                <div class="stat-label">Professional templates available</div>
            </div>
        </div>
    </div>

    <!-- ─── Features ─── -->
    <section class="features-section" id="features">
        <div class="section-inner">
            <div class="features-header">
                <div class="reveal">
                    <p class="eyebrow">Features</p>
                    <h2 class="section-title">Everything you need to<br><em>stand out</em></h2>
                </div>
                <p class="section-subtitle reveal" style="transition-delay:0.1s">
                    Purpose-built tools that help you craft a resume hiring managers actually want to read — and ATS systems rank highly.
                </p>
            </div>
            <div class="features-grid reveal" style="transition-delay:0.15s">
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fas fa-magic"></i></div>
                    <div class="feature-name">AI-Powered Suggestions</div>
                    <p class="feature-desc">Intelligent keyword recommendations based on your industry and target roles, drawn from real job postings.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fas fa-eye"></i></div>
                    <div class="feature-name">Live Preview</div>
                    <p class="feature-desc">See exactly how your resume looks as you type. Every change reflected in real time across all templates.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fas fa-shield-alt"></i></div>
                    <div class="feature-name">ATS Optimized</div>
                    <p class="feature-desc">Every template is tested against major Applicant Tracking Systems so your resume gets past the robots first.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fas fa-palette"></i></div>
                    <div class="feature-name">Pro Templates</div>
                    <p class="feature-desc">Designed by career experts. Clean, elegant layouts that make your experience the focus — not the design.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fas fa-file-download"></i></div>
                    <div class="feature-name">PDF Export</div>
                    <p class="feature-desc">Download a pixel-perfect PDF instantly. Consistent rendering across all devices and operating systems.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrap"><i class="fas fa-sync-alt"></i></div>
                    <div class="feature-name">Always Editable</div>
                    <p class="feature-desc">Your data is saved automatically. Revisit, update, and re-download your resume whenever things change.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── How It Works ─── -->
    <section id="how-it-works">
        <div class="section-inner">
            <div class="reveal">
                <p class="eyebrow">Process</p>
                <h2 class="section-title">Four steps to your<br><em>perfect resume</em></h2>
            </div>
            <div class="steps-container">
                <div class="steps-line"></div>
                <div class="step-card reveal" style="transition-delay:0.05s">
                    <div class="step-num">01</div>
                    <div class="step-title">Create your account</div>
                    <p class="step-desc">Sign up in under a minute. No credit card required to get started with your first resume.</p>
                </div>
                <div class="step-card reveal" style="transition-delay:0.1s">
                    <div class="step-num">02</div>
                    <div class="step-title">Pick a template</div>
                    <p class="step-desc">Browse our gallery of professionally designed templates. Filter by industry, style, or ATS rating.</p>
                </div>
                <div class="step-card reveal" style="transition-delay:0.15s">
                    <div class="step-num">03</div>
                    <div class="step-title">Fill in your details</div>
                    <p class="step-desc">Our guided builder walks you through every section. AI suggestions help you phrase things perfectly.</p>
                </div>
                <div class="step-card reveal" style="transition-delay:0.2s">
                    <div class="step-num">04</div>
                    <div class="step-title">Download & apply</div>
                    <p class="step-desc">Export a flawless PDF and send it out. Most users land their first interview within two weeks.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Templates ─── -->
    <section class="templates-section" id="templates">
        <div class="section-inner">
            <div class="templates-header">
                <div class="reveal">
                    <p class="eyebrow">Templates</p>
                    <h2 class="section-title">Designed for every<br><em>career path</em></h2>
                </div>
                <a href="<?= BASE_URL ?>/templates" class="btn-primary reveal" style="transition-delay:0.1s">
                    Browse All Templates <i class="fas fa-arrow-right" style="font-size:12px"></i>
                </a>
            </div>

            <div class="template-strip reveal" style="transition-delay:0.1s">
                <!-- Template cards (decorative previews) -->
                <div class="template-card-preview t1">
                    <div class="template-thumb">
                        <div class="template-thumb-inner">
                            <div class="tt-header" style="background:rgba(201,168,76,0.08)">
                                <div class="tt-avatar"></div>
                                <div class="tt-name"></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-section-head"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w80"></div>
                                <div class="tt-line w60"></div>
                                <div class="tt-section-head" style="margin-top:8px"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w60"></div>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <span class="template-label">Classic Gold</span>
                        <span class="template-tag">Popular</span>
                    </div>
                </div>

                <div class="template-card-preview t2">
                    <div class="template-thumb">
                        <div class="template-thumb-inner">
                            <div class="tt-header" style="background:rgba(99,102,241,0.1)">
                                <div class="tt-avatar" style="background:rgba(99,102,241,0.3)"></div>
                                <div class="tt-name"></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-section-head"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w80"></div>
                                <div class="tt-line w60"></div>
                                <div class="tt-section-head" style="margin-top:8px"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w60"></div>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <span class="template-label">Modern Blue</span>
                        <span class="template-tag">ATS-Friendly</span>
                    </div>
                </div>

                <div class="template-card-preview t3">
                    <div class="template-thumb">
                        <div class="template-thumb-inner">
                            <div class="tt-header" style="background:rgba(16,185,129,0.08)">
                                <div class="tt-avatar" style="background:rgba(16,185,129,0.3)"></div>
                                <div class="tt-name"></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-section-head"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w80"></div>
                                <div class="tt-line w60"></div>
                                <div class="tt-section-head" style="margin-top:8px"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w60"></div>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <span class="template-label">Executive Green</span>
                        <span class="template-tag">New</span>
                    </div>
                </div>

                <div class="template-card-preview t4">
                    <div class="template-thumb">
                        <div class="template-thumb-inner">
                            <div class="tt-header" style="background:rgba(236,72,153,0.08)">
                                <div class="tt-avatar" style="background:rgba(236,72,153,0.3)"></div>
                                <div class="tt-name"></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-section-head"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w80"></div>
                                <div class="tt-line w60"></div>
                                <div class="tt-section-head" style="margin-top:8px"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w60"></div>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <span class="template-label">Creative Pink</span>
                        <span class="template-tag">Bold</span>
                    </div>
                </div>

                <div class="template-card-preview t5">
                    <div class="template-thumb">
                        <div class="template-thumb-inner">
                            <div class="tt-header" style="background:rgba(245,158,11,0.08)">
                                <div class="tt-avatar" style="background:rgba(245,158,11,0.3)"></div>
                                <div class="tt-name"></div>
                            </div>
                            <div class="tt-body">
                                <div class="tt-section-head"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w80"></div>
                                <div class="tt-line w60"></div>
                                <div class="tt-section-head" style="margin-top:8px"></div>
                                <div class="tt-line"></div>
                                <div class="tt-line w60"></div>
                            </div>
                        </div>
                    </div>
                    <div class="template-info">
                        <span class="template-label">Orange Zest</span>
                        <span class="template-tag">Standout</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── Testimonials ─── -->
    <section id="testimonials">
        <div class="section-inner">
            <div class="testimonials-header reveal">
                <p class="eyebrow">Testimonials</p>
                <h2 class="section-title">Real stories from<br><em>real hires</em></h2>
            </div>
            <div class="testimonials-grid">
                <div class="testi-card reveal" style="transition-delay:0.05s">
                    <div class="testi-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="testi-quote">"I had been applying for months with zero callbacks. After rebuilding my CV here, I had three interviews scheduled within a week. The templates are genuinely beautiful."</p>
                    <div class="testi-author">
                        <div class="testi-avatar">J</div>
                        <div>
                            <div class="testi-name">John Asante</div>
                            <div class="testi-role">Software Engineer, Accra</div>
                        </div>
                    </div>
                </div>
                <div class="testi-card reveal" style="transition-delay:0.1s">
                    <div class="testi-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="testi-quote">"The AI keyword suggestions were a revelation. I never would have known what phrases recruiters search for. Landed my dream role at a multinational within a month."</p>
                    <div class="testi-author">
                        <div class="testi-avatar">S</div>
                        <div>
                            <div class="testi-name">Sarah Mensah</div>
                            <div class="testi-role">Marketing Manager, Lagos</div>
                        </div>
                    </div>
                </div>
                <div class="testi-card reveal" style="transition-delay:0.15s">
                    <div class="testi-stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p class="testi-quote">"Clean, fast, and the PDF export looks flawless on every device. I keep coming back every time I update my experience. Worth every second."</p>
                    <div class="testi-author">
                        <div class="testi-avatar">M</div>
                        <div>
                            <div class="testi-name">Michael Chen</div>
                            <div class="testi-role">Product Designer, Nairobi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ─── CTA ─── -->
    <div class="cta-section">
        <div class="cta-box reveal">
            <p class="eyebrow" style="justify-content:center">Get Started</p>
            <h2 class="section-title">Your next opportunity<br>starts with a great <em>resume</em></h2>
            <p class="section-subtitle" style="text-align:center;margin:0 auto 44px">
                Join thousands of professionals who've already landed roles they love. Free to start, takes minutes.
            </p>
            <div class="cta-actions">
                <a href="<?= BASE_URL ?>/dashboard" class="btn-primary" style="font-size:16px;padding:16px 40px">
                    Build My Resume Now <i class="fas fa-arrow-right" style="font-size:13px"></i>
                </a>
            </div>
            <p class="cta-note">Free to use · No credit card required · Download as PDF</p>
        </div>
    </div>

    <!-- ─── Footer ─── -->
    <footer>
        <div class="footer-inner">
            <div>
                <div class="footer-brand-name">
                    <span class="nav-logo-dot"></span> Bright CV
                </div>
                <p class="footer-brand-desc">
                    Professional resume builder for modern careers. Craft a resume that opens doors.
                </p>
                <div class="footer-socials">
                    <a href="#" class="footer-social"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="footer-social"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="footer-social"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div>
                <div class="footer-col-title">Product</div>
                <ul class="footer-links">
                    <li><a href="#">Templates</a></li>
                    <li><a href="#">Builder</a></li>
                    <li><a href="#">AI Features</a></li>
                    <li><a href="#">PDF Export</a></li>
                </ul>
            </div>
            <div>
                <div class="footer-col-title">Resources</div>
                <ul class="footer-links">
                    <li><a href="#">Resume Guide</a></li>
                    <li><a href="#">Career Blog</a></li>
                    <li><a href="#">Examples</a></li>
                    <li><a href="#">FAQ</a></li>
                </ul>
            </div>
            <div>
                <div class="footer-col-title">Company</div>
                <ul class="footer-links">
                    <li><a href="#">About</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Privacy</a></li>
                    <li><a href="#">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span class="footer-copy">© 2026 Bright CV · All rights reserved</span>
            <div class="footer-legal">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>/assets/landing/landing.js"></script>
</body>

</html>