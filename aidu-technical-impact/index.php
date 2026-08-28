<?php
require __DIR__.'/config/bootstrap.php';
$page_title = site_setting('company_name');
require __DIR__.'/includes/header.php';
require __DIR__.'/includes/splash.php';
// Each section loads independently: one failing query greys out that section
// instead of taking the whole homepage down.
$services = db_rows('SELECT * FROM services WHERE active=1 ORDER BY sort_order,id LIMIT 6');
$projects = db_rows("SELECT p.*, EXISTS(SELECT 1 FROM project_media pm WHERE pm.project_id=p.id AND pm.media_type='video') AS has_video FROM projects p WHERE p.featured=1 ORDER BY p.created_at DESC LIMIT 3");
$sectors = db_rows('SELECT * FROM sectors WHERE active=1 ORDER BY sort_order,id');
$testimonials = db_rows('SELECT * FROM testimonials WHERE active=1 ORDER BY created_at DESC LIMIT 3');
// The previous default pointed at a Wikimedia article page, not an image file,
// so the "Why AID-U" panel showed a broken image on a fresh install.
$whyImage = site_setting('why_image', 'assets/images/why-site-context.jpg');
?>
<section class="hero">
    <div class="container hero-content">
        <span class="eyebrow">AID-U-TECHNICAL IMPACT</span>
        <h1><?=e(site_setting('hero_title'))?></h1>
        <p><?=e(site_setting('hero_text'))?></p>
        <div class="hero-actions">
            <a class="button" href="<?=e(url('contact.php'))?>"><?=e(site_setting('primary_cta','Request a Consultation'))?> <i class="fa-solid fa-arrow-right"></i></a>
            <a class="button alt" href="<?=e(url('projects.php'))?>">Explore Projects</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">What we do</span><h2>Technical work built around accuracy.</h2></div>
            <p>From field measurement to technical drawings and construction support, we help turn site information into practical project decisions.</p>
        </div>
        <div class="grid-3">
            <?php if(!$services): ?>
                <p class="empty-state">Services will appear here once they are added in <strong>Admin &rarr; Services</strong>.</p>
            <?php endif; ?>
            <?php foreach($services as $s): ?>
                <article class="service-card">
                    <div class="service-icon"><i class="<?=e($s['icon'])?>"></i></div>
                    <h3><?=e($s['title'])?></h3>
                    <p><?=e($s['short_text'])?></p>
                    <a class="project-link" href="<?=e(url('services.php'))?>">Learn more <i class="fa-solid fa-arrow-right"></i></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section about-home-section">
    <div class="container split">
        <div class="about-visual-card">
            <div class="about-visual-top">
                <span class="eyebrow">About AID-U</span>
                <span class="about-badge"><i class="fa-solid fa-ruler-combined"></i> Technical Precision</span>
            </div>
            <div class="about-mark">
                <strong>ATI</strong>
                <span>ARCHITECTURAL • ENGINEERING • SURVEYING</span>
            </div>
            <div class="about-visual-lines"><span></span><span></span><span></span></div>
            <div class="about-visual-bottom"><strong>PRECISE AND CONCISE</strong><small>Field data • Drafting • Site support</small></div>
        </div>
        <div>
            <span class="eyebrow">Who we are</span>
            <h2>Professional technical support from the site to the drawing.</h2>
            <p><?=nl2br(e(site_setting('about_text')))?></p>
            <ul class="check-list">
                <li><i class="fa-solid fa-circle-check"></i> Practical surveying and site information.</li>
                <li><i class="fa-solid fa-circle-check"></i> Professional technical drafting and documentation.</li>
                <li><i class="fa-solid fa-circle-check"></i> Construction and project support based on real site conditions.</li>
                <li><i class="fa-solid fa-circle-check"></i> Clear communication from first enquiry to project delivery.</li>
            </ul>
            <a class="button" href="<?=e(url('about.php'))?>">Discover AID-U <i class="fa-solid fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<section class="section why-section">
    <div class="container split">
        <div class="context-image why-context-image">
            <img src="<?=e(asset_url($whyImage))?>" alt="Real site, building and road context">
            <div class="image-label">REAL SITE / BUILDING / ROAD CONTEXT</div>
        </div>
        <div>
            <span class="eyebrow">Why AID-U</span>
            <h2>We work from what is actually on the ground.</h2>
            <p>Good technical decisions start with reliable site information. Our approach connects surveying, drafting, architectural thinking and engineering support so the information is useful beyond the field.</p>
            <div class="why-points">
                <div><i class="fa-solid fa-crosshairs"></i><div><strong>Site-first accuracy</strong><span>Measurements and observations are organized around real site conditions.</span></div></div>
                <div><i class="fa-solid fa-compass-drafting"></i><div><strong>Clear technical drawings</strong><span>Drawings communicate dimensions, intent and construction information clearly.</span></div></div>
                <div><i class="fa-solid fa-photo-film"></i><div><strong>Useful project records</strong><span>Images and videos can document progress, context and completed work.</span></div></div>
            </div>
        </div>
    </div>
</section>

<section class="section" id="projects">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Selected work</span><h2>Projects that show the work.</h2></div>
            <a class="button alt" href="<?=e(url('projects.php'))?>">View all projects</a>
        </div>
        <div class="project-grid">
            <?php if(!$projects): ?>
                <p class="empty-state">No featured projects yet. Tick <strong>Feature this project on the homepage</strong> in <strong>Admin &rarr; Projects</strong> to show work here.</p>
            <?php endif; ?>
            <?php foreach($projects as $p): ?>
                <article class="project-card">
                    <a class="project-img" href="<?=e(url('project.php?id='.(int)$p['id']))?>" aria-label="View details for <?=e($p['title'])?>">
                        <?php $img=$p['cover_image'] ?: $whyImage; ?>
                        <img src="<?=e(asset_url($img))?>" alt="<?=e($p['title'])?>">
                        <?php if(!empty($p['project_video']) || !empty($p['has_video'])): ?><span class="project-video-badge"><i class="fa-solid fa-play"></i> Video</span><?php endif; ?>
                        <span class="project-view-badge"><i class="fa-solid fa-eye"></i> View project details</span>
                    </a>
                    <div class="project-body">
                        <div class="project-meta"><span><?=e($p['category'])?></span><span><?=e($p['year_label'])?></span></div>
                        <h3><?=e($p['title'])?></h3>
                        <p><?=e($p['short_description'])?></p>
                        <a class="project-link" href="<?=e(url('project.php?id='.(int)$p['id']))?>">View project <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Built for every sector</span><h2>Where our technical services fit.</h2></div>
            <p>Sectors stay out of the main navigation while the homepage and footer show the breadth of work.</p>
        </div>
        <div class="sectors-grid">
            <?php if(!$sectors): ?>
                <p class="empty-state">Sectors will appear here once they are added in <strong>Admin &rarr; Sectors</strong>.</p>
            <?php endif; ?>
            <?php foreach($sectors as $s): ?>
                <article class="sector-card"><i class="<?=e($s['icon'])?>"></i><h3><?=e($s['title'])?></h3><p><?=e($s['description'])?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Client perspective</span><h2>Professional communication matters.</h2></div></div>
        <div class="grid-3">
            <?php if(!$testimonials): ?>
                <p class="empty-state">Client feedback will appear here once it is added in <strong>Admin &rarr; Testimonials</strong>.</p>
            <?php endif; ?>
            <?php foreach($testimonials as $t): ?>
                <article class="testimonial"><div class="stars"><?=str_repeat('★',max(1,min(5,(int)($t['rating']??5))))?></div><p>“<?=e($t['quote'])?>”</p><div class="person"><div class="person-avatar"><?=e(strtoupper(substr((string)($t['client_name']??'?'),0,1) ?: '?'))?></div><div><strong><?=e($t['client_name'])?></strong><small><?=e($t['role_company'])?></small></div></div></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section"><div class="container"><div class="cta-band"><div><span class="eyebrow">Have a site or project?</span><h2>Let’s discuss the technical work.</h2><p>Send an enquiry and receive the message options immediately.</p></div><a class="button" href="<?=e(url('contact.php'))?>">Send Enquiry <i class="fa-solid fa-arrow-right"></i></a></div></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
