<?php
require __DIR__.'/config/bootstrap.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){header('Location: '.url('projects.php'));exit;}

$p = db_row('SELECT * FROM projects WHERE id=?', [$id]);
if(!$p){
    // Previously this printed the bare words "Project not found." on a blank
    // white page. Now the visitor gets the branded page and a way forward.
    app_render_error_page(
        404,
        'That project is no longer available',
        'The project you opened has been removed, or the link is out of date.',
        [
            'Browse the full project library from the Projects page.',
            'Use the menu to find services, sectors or contact details.',
            'If you followed a link from elsewhere, let us know so we can fix it.'
        ]
    );
    exit;
}

$media = db_rows('SELECT * FROM project_media WHERE project_id=? ORDER BY sort_order,id', [$id]);
$page_title=$p['title'].' · '.site_setting('company_name');
require __DIR__.'/includes/header.php';
$cover=$p['cover_image']?:site_setting('why_image','assets/images/splash-real-site-road.jpg');
?>
<section class="page-hero"><div class="container"><span class="eyebrow"><?=e($p['category'])?></span><h1><?=e($p['title'])?></h1><p><?=e($p['short_description'])?></p></div></section>
<section class="project-detail"><div class="container">
    <div class="detail-cover"><img src="<?=e(asset_url($cover))?>" alt="<?=e($p['title'])?>"></div>
    <div class="detail-layout">
        <div>
            <h2>Project overview</h2>
            <p><?=nl2br(e($p['description']))?></p>
            <?php if(!empty($p['project_video'])): ?>
                <h2 style="margin-top:40px">Project video</h2>
                <div class="media-card project-video-card">
                    <video controls preload="metadata" playsinline controlsList="nodownload" poster="<?=e(asset_url($cover))?>">
                        <source src="<?=e(asset_url($p['project_video']))?>" type="<?=e(media_type_for($p['project_video']))?>">
                        Your browser cannot play this video.
                    </video>
                    <div class="media-actions"><a href="<?=e(asset_url($p['project_video']))?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open video directly</a></div>
                </div>
            <?php endif; ?>
            <?php if($media): ?>
                <h2 style="margin-top:45px">Project images & videos</h2>
                <div class="media-gallery">
                    <?php foreach($media as $item): ?>
                        <div class="media-card">
                            <?php if($item['media_type']==='image'): ?>
                                <img src="<?=e(asset_url($item['file_path']))?>" alt="<?=e(($item['caption']??'')?:$p['title'])?>">
                            <?php else: ?>
                                <video controls preload="metadata" playsinline poster="<?=e(asset_url($cover))?>">
                                    <source src="<?=e(asset_url($item['file_path']))?>" type="<?=e(media_type_for($item['file_path']))?>">
                                    Your browser cannot play this video.
                                </video>
                                <div class="media-actions"><a href="<?=e(asset_url($item['file_path']))?>" target="_blank" rel="noopener">Open video</a></div>
                            <?php endif; ?>
                            <?php if(!empty($item['caption'])): ?><div class="media-caption"><?=e($item['caption'])?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <aside class="project-info">
            <div><small>Category</small><?=e($p['category'])?></div><div><small>Location</small><?=e($p['location'])?></div><div><small>Year</small><?=e($p['year_label'])?></div><div><small>Client</small><?=e(($p['client_name']??'')?:'Private Client')?></div><div><small>Status</small><?=e($p['status'])?></div>
            <a class="button" style="width:100%;margin-top:15px" href="<?=e(url('contact.php'))?>">Discuss a project <i class="fa-solid fa-arrow-right"></i></a>
        </aside>
    </div>
</div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
