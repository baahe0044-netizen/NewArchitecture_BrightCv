<?php
require __DIR__.'/../config/bootstrap.php';
require_admin(); // these totals were being counted before any login check ran
$admin_title='Dashboard';
// db_value() returns 0 rather than throwing, so a database hiccup shows an
// empty dashboard with a warning instead of a fatal error page.
$counts=[
 'projects'=>(int)db_value('SELECT COUNT(*) FROM projects'),
 'services'=>(int)db_value('SELECT COUNT(*) FROM services WHERE active=1'),
 'messages'=>(int)db_value("SELECT COUNT(*) FROM contact_messages WHERE status='New'"),
 'socials'=>(int)db_value('SELECT COUNT(*) FROM social_links WHERE active=1')
];
if (!db_available()) {
    flash('error', 'The dashboard could not reach the database, so the totals below show zero. Check that MySQL is running and that database.sql has been imported.');
}
require __DIR__.'/../includes/admin_header.php';
?>
<section class="dashboard-head">
 <div><span class="dashboard-kicker">ADMINISTRATION</span><h2>Website Dashboard</h2><p>Manage the AID-U website from one straightforward control panel.</p></div>
 <a class="dashboard-primary" href="<?=e(url('admin/projects.php'))?>"><i class="fa-solid fa-plus"></i> New Project</a>
</section>
<section class="dashboard-stats" aria-label="Website totals">
 <a class="dashboard-stat projects" href="<?=e(url('admin/projects.php'))?>"><span class="stat-icon"><i class="fa-solid fa-building"></i></span><span><strong><?=$counts['projects']?></strong><small>Projects</small></span></a>
 <a class="dashboard-stat services" href="<?=e(url('admin/services.php'))?>"><span class="stat-icon"><i class="fa-solid fa-compass-drafting"></i></span><span><strong><?=$counts['services']?></strong><small>Active Services</small></span></a>
 <a class="dashboard-stat enquiries" href="<?=e(url('admin/messages.php'))?>"><span class="stat-icon"><i class="fa-solid fa-envelope"></i></span><span><strong><?=$counts['messages']?></strong><small>New Enquiries</small></span></a>
 <a class="dashboard-stat socials" href="<?=e(url('admin/socials.php'))?>"><span class="stat-icon"><i class="fa-solid fa-share-nodes"></i></span><span><strong><?=$counts['socials']?></strong><small>Social Accounts</small></span></a>
</section>
<section class="dashboard-columns">
 <div class="admin-card"><div class="admin-card-head"><div><h2>Quick access</h2><p>Common website tasks.</p></div></div><div class="quick-list">
 <a href="<?=e(url('admin/projects.php'))?>"><i class="fa-solid fa-building"></i><span><b>Projects</b><small>Manage photos, videos and descriptions</small></span><i class="fa-solid fa-chevron-right"></i></a>
 <a href="<?=e(url('admin/services.php'))?>"><i class="fa-solid fa-compass-drafting"></i><span><b>Services</b><small>Edit the services shown to visitors</small></span><i class="fa-solid fa-chevron-right"></i></a>
 <a href="<?=e(url('admin/messages.php'))?>"><i class="fa-solid fa-envelope"></i><span><b>Enquiries</b><small>Review client enquiries and WhatsApp status</small></span><i class="fa-solid fa-chevron-right"></i></a>
 </div></div>
 <div class="admin-card dashboard-notes"><div class="admin-card-head"><div><h2>System notes</h2><p>Things worth checking before going live.</p></div></div>
 <div class="note-row"><i class="fa-solid fa-phone"></i><span><b>Company phone</b><small><?=e(site_setting('phone','Not configured'))?></small></span></div>
 <div class="note-row"><i class="fa-solid fa-envelope"></i><span><b>Company email</b><small><?=e(site_setting('email','Not configured'))?></small></span></div>
 <a class="plain-link" href="<?=e(url('admin/account.php'))?>">Manage administrator account <i class="fa-solid fa-arrow-right"></i></a>
 </div>
</section>
<?php require __DIR__.'/../includes/admin_footer.php'; ?>