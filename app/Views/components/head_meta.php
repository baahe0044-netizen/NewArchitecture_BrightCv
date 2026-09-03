<?php
/**
 * Shared document head: responsive viewport, installable-app metadata, and
 * icons. Included by every view so a change here reaches the whole app.
 *
 * $pageDescription may be set by a view to describe that page specifically.
 */
$description = $pageDescription
    ?? 'Write, tailor, and export a professional CV from any device with ' . APP_NAME . '.';
?>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="app-url" content="<?= e(BASE_URL) ?>">
<meta name="description" content="<?= e($description) ?>">
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#EBD9BB">
<meta name="format-detection" content="telephone=no">

<?php /* Tinos: metrically identical to Times New Roman, so a blocked or slow
         request falls back to real Times with no reflow. One shared link so
         every page picks it up the same way. */ ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tinos:ital,wght@0,400;0,700;1,400&display=swap">

<link rel="manifest" href="<?= e(base_url('/manifest.webmanifest')) ?>">
<link rel="icon" href="<?= e(asset('icons/icon-192.png')) ?>" sizes="192x192" type="image/png">
<link rel="icon" href="<?= e(asset('icons/icon-512.png')) ?>" sizes="512x512" type="image/png">
<link rel="apple-touch-icon" href="<?= e(asset('icons/apple-touch-icon.png')) ?>">

<meta name="application-name" content="<?= e(APP_NAME) ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
