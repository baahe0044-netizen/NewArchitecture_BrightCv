<?php
/**
 * Not inline: the app sends a strict script-src 'self' CSP with no
 * 'unsafe-inline' (app/Core/App.php). A same-origin, non-deferred <script
 * src> still blocks parsing before the stylesheets paint — same anti-flash
 * timing as an inline script — without weakening the CSP.
 */
?>
<script src="<?= e(asset('common/theme-init.js')) ?>"></script>
