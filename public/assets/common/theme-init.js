(function () {
  var mode = 'system';
  var palette = 'parchment';

  try {
    var storedMode = localStorage.getItem('brightcv-theme');
    if (storedMode === 'light' || storedMode === 'dark') {
      mode = storedMode;
    }
    var storedPalette = localStorage.getItem('brightcv:palette');
    if (['parchment', 'azure', 'mono', 'ember'].indexOf(storedPalette) !== -1) {
      palette = storedPalette;
    }
  } catch (error) {
    /* localStorage unavailable (private mode, disabled storage) — fall back to
       the default palette and the system preference via CSS. */
  }

  // data-theme drives light against dark and is only set for explicit
  // overrides, so the prefers-color-scheme rules stay in charge under
  // "system". data-theme-mode always reflects the chosen mode so the header
  // toggle shows the right icon on first paint, without waiting for deferred JS.
  if (mode !== 'system') {
    document.documentElement.setAttribute('data-theme', mode);
  }
  document.documentElement.setAttribute('data-theme-mode', mode);

  // The palette is a separate axis: it chooses the colour family, and light
  // against dark still follows the device. Always set, so the attribute
  // selectors resolve on the first paint rather than after a flash.
  document.documentElement.setAttribute('data-palette', palette);
})();
