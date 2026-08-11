(function () {
  var mode = 'system';

  try {
    var stored = localStorage.getItem('brightcv-theme');
    if (stored === 'light' || stored === 'dark') {
      mode = stored;
    }
  } catch (error) {
    /* localStorage unavailable (private mode, disabled storage) — fall back to system preference via CSS. */
  }

  // data-theme drives the palette and is only set for explicit overrides, so
  // the prefers-color-scheme rules stay in charge under "system".
  // data-theme-mode always reflects the chosen mode so the header toggle can
  // show the right icon on first paint, without waiting for deferred JS.
  if (mode !== 'system') {
    document.documentElement.setAttribute('data-theme', mode);
  }
  document.documentElement.setAttribute('data-theme-mode', mode);
})();
