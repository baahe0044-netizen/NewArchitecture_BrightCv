<?php
require_once __DIR__.'/../config/bootstrap.php';

// If the database cannot be reached at all, show the branded explanation
// rather than an empty shell of a website with no content in it.
if (!db_available()) {
    try {
        db();
    } catch (Throwable $e) {
        app_handle_exception($e);
    }
}

// A half-finished database import used to leave a silent, empty website.
db_require_schema();

$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="<?=e(site_setting('company_name'))?> — <?=e(site_setting('tagline'))?>"><title><?=e($page_title??site_setting('company_name'))?></title><link rel="icon" href="<?=e(url('assets/images/aid-u-technical-impact-logo.jpg'))?>"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"><link rel="stylesheet" href="<?=e(url('assets/css/style.css'))?>"></head><body>
<header class="site-header"><div class="container nav-wrap"><a class="brand" href="<?=e(url('index.php'))?>"><img src="<?=e(url('assets/images/aid-u-technical-impact-logo.jpg'))?>" alt="<?=e(site_setting('company_name'))?>"><span><?=e(site_setting('company_name'))?></span></a><button class="menu-toggle" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button><nav class="main-nav"><a class="<?= $current==='index.php'?'active':'' ?>" href="<?=e(url('index.php'))?>">Home</a><a class="<?= $current==='services.php'?'active':'' ?>" href="<?=e(url('services.php'))?>">Services</a><a class="<?= $current==='projects.php' || $current==='project.php'?'active':'' ?>" href="<?=e(url('projects.php'))?>">Projects</a><a class="<?= $current==='about.php'?'active':'' ?>" href="<?=e(url('about.php'))?>">About</a><a class="<?= $current==='contact.php'?'active':'' ?>" href="<?=e(url('contact.php'))?>">Contact</a></nav><a class="nav-cta" href="<?=e(url('contact.php'))?>">Start a Project <i class="fa-solid fa-arrow-right"></i></a></div></header>
<main>