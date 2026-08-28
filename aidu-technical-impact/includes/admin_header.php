<?php
require_once __DIR__.'/../config/bootstrap.php';
require_admin();
db_require_schema();
$adminName=$_SESSION['admin_name']??'Administrator';
$flashes=get_flashes();
$current=basename($_SERVER['SCRIPT_NAME']??'');
// Guarded: including this header twice used to abort the page with
// "Cannot redeclare admin_active()".
if (!function_exists('admin_active')) {
    function admin_active(string $file): string { global $current; return $current === $file ? 'active' : ''; }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($admin_title??'Admin')?> · <?=e(site_setting('company_name'))?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="<?=e(url('assets/css/admin.css'))?>">
</head>
<body>
<aside class="sidebar">
<a class="admin-brand" href="<?=e(url('admin/index.php'))?>">
<img src="<?=e(url('assets/images/aid-u-technical-impact-logo.jpg'))?>" alt="AID-U Technical Impact">
<span class="admin-brand-copy"><strong>AID-U TECHNICAL IMPACT</strong><span>CONTROL CENTRE</span></span>
</a>
<div class="nav-label">Website Management</div>
<nav>
<a class="<?=admin_active('index.php')?>" href="<?=e(url('admin/index.php'))?>"><i class="fa-solid fa-gauge-high"></i>Dashboard</a>
<a class="<?=admin_active('projects.php')?>" href="<?=e(url('admin/projects.php'))?>"><i class="fa-solid fa-building"></i>Projects</a>
<a class="<?=admin_active('services.php')?>" href="<?=e(url('admin/services.php'))?>"><i class="fa-solid fa-compass-drafting"></i>Services</a>
<a class="<?=admin_active('sectors.php')?>" href="<?=e(url('admin/sectors.php'))?>"><i class="fa-solid fa-layer-group"></i>Sectors</a>
<a class="<?=admin_active('testimonials.php')?>" href="<?=e(url('admin/testimonials.php'))?>"><i class="fa-solid fa-star"></i>Testimonials</a>
<a class="<?=admin_active('socials.php')?>" href="<?=e(url('admin/socials.php'))?>"><i class="fa-solid fa-share-nodes"></i>Social Media</a>
<a class="<?=admin_active('messages.php')?>" href="<?=e(url('admin/messages.php'))?>"><i class="fa-solid fa-envelope"></i>Enquiries</a>
<a class="<?=admin_active('settings.php')?>" href="<?=e(url('admin/settings.php'))?>"><i class="fa-solid fa-sliders"></i>Site Settings</a>
</nav>
<div class="nav-label">Account</div>
<nav>
<a class="<?=admin_active('account.php')?>" href="<?=e(url('admin/account.php'))?>"><i class="fa-solid fa-user-shield"></i>Admin Account</a>
<a href="<?=e(url('index.php'))?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i>View Website</a>
</nav>
<div class="sidebar-footer"><a href="<?=e(url('admin/logout.php'))?>"><i class="fa-solid fa-right-from-bracket"></i>Sign out</a></div>
</aside>
<div class="admin-main">
<header class="admin-top">
<div><h1><?=e($admin_title??'Dashboard')?></h1><span>Signed in as <?=e($adminName)?></span></div>
<div class="admin-top-right"><div class="admin-user-chip"><i class="fa-solid fa-shield-halved"></i><?=e($adminName)?></div><div class="admin-logo-mini"><img src="<?=e(url('assets/images/aid-u-technical-impact-logo.jpg'))?>" alt="Logo"></div></div>
</header>
<?php foreach($flashes as [$type,$msg]): ?>
<div class="admin-alert <?=e($type)?>" role="<?= $type==='error'?'alert':'status' ?>">
<i class="fa-solid <?= $type==='error'?'fa-circle-exclamation':'fa-circle-check' ?>"></i> <?=e($msg)?>
</div>
<?php endforeach; ?>
