<?php
/**
 * Cycles System → Light → Dark. Wired in public/assets/common/app.js, which
 * also keeps the accessible label describing both the current mode and what
 * the next activation will do. The visible icon is chosen by CSS from
 * data-theme-mode on <html>, so it is correct on first paint.
 */
?>
<button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Change theme" title="Change theme">
    <svg data-theme-icon="system" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
    <svg data-theme-icon="light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2v2.4M12 19.6V22M2 12h2.4M19.6 12H22M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M19.1 4.9l-1.7 1.7M6.6 17.4l-1.7 1.7"/></svg>
    <svg data-theme-icon="dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 1 1 9.8 3.5a6.8 6.8 0 0 0 10.7 10.7Z"/></svg>
</button>
