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
<?php /* interactive-widget: on Android the on-screen keyboard shrinks the page
         instead of sliding over it, so fixed panels end above the keyboard and
         the field being typed in stays on screen. */ ?>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content, maximum-scale=1, user-scalable=no">
<meta name="app-url" content="<?= e(BASE_URL) ?>">
<meta name="description" content="<?= e($description) ?>">
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#1B1025">
<meta name="format-detection" content="telephone=no">

<?php /* The app is set in Calibri. Calibri itself cannot be served from a
         website (it is licensed with Windows and Office), so devices that
         have it use it, and everything else gets Carlito: a free font drawn
         to Calibri's exact letter widths. One shared link for every page. */ ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Carlito:ital,wght@0,400;0,700;1,400;1,700&display=swap">

<link rel="manifest" href="<?= e(base_url('/manifest.webmanifest')) ?>">
<link rel="icon" href="<?= e(asset('icons/icon-192.png')) ?>" sizes="192x192" type="image/png">
<link rel="icon" href="<?= e(asset('icons/icon-512.png')) ?>" sizes="512x512" type="image/png">
<link rel="apple-touch-icon" href="<?= e(asset('icons/apple-touch-icon.png')) ?>">

<meta name="application-name" content="<?= e(APP_NAME) ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_NAME) ?>">
<?php /* "black", not "black-translucent": a translucent status bar lays the
         installed app underneath it on iPhone, where it swallows taps on the
         top bar. */ ?>
<meta name="apple-mobile-web-app-status-bar-style" content="black">
