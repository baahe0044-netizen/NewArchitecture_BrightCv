<?php
require __DIR__.'/config/bootstrap.php';
$page_title='Projects · '.site_setting('company_name');
require __DIR__.'/includes/header.php';
$items=db_rows('SELECT * FROM projects ORDER BY featured DESC, created_at DESC');
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Project library</span><h1>Real work. Clear documentation.</h1><p>Explore project pages with photographs and videos. Every project can be updated from the admin dashboard.</p></div></section>
<section class="section"><div class="container"><div class="project-grid">
<?php if(!$items): ?>
<p class="empty-state">There are no projects to show yet. Projects added in <strong>Admin &rarr; Projects</strong> appear here automatically.</p>
<?php endif; ?>
<?php foreach($items as $p): $img=$p['cover_image']?:site_setting('why_image','assets/images/splash-real-site-road.jpg'); ?>
<article class="project-card">
    <a class="project-img" href="<?=e(url('project.php?id='.(int)$p['id']))?>" aria-label="View details for <?=e($p['title'])?>">
        <img src="<?=e(asset_url($img))?>" alt="<?=e($p['title'])?>">
        <?php if(!empty($p['project_video'])): ?><span class="project-video-badge"><i class="fa-solid fa-play"></i> Video</span><?php endif; ?>
        <span class="project-view-badge"><i class="fa-solid fa-eye"></i> View project details</span>
    </a>
    <div class="project-body">
        <div class="project-meta"><span><?=e($p['category'])?></span><span><?=e($p['status'])?></span></div>
        <h3><?=e($p['title'])?></h3>
        <p><?=e($p['short_description'])?></p>
        <a class="project-link" href="<?=e(url('project.php?id='.(int)$p['id']))?>">View project <i class="fa-solid fa-arrow-right"></i></a>
    </div>
</article>
<?php endforeach; ?>
</div></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
