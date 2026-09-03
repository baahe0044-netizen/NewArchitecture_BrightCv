(() => {
  'use strict';

  const inputs = document.querySelectorAll('input[name="theme"]');
  const theme = window.BrightTheme;
  if (!inputs.length || !theme) {
    return;
  }

  const syncInputs = (mode) => {
    inputs.forEach((input) => {
      input.checked = input.value === mode;
    });
  };

  syncInputs(theme.get());

  inputs.forEach((input) => {
    input.addEventListener('change', () => {
      if (!input.checked) return;
      theme.set(input.value);
      window.Lunetti?.toast?.(`Theme set to ${theme.label(input.value)}.`);
    });
  });

  // Keep the radios correct if the header toggle changes the theme.
  document.addEventListener('brightcv:themechange', (event) => syncInputs(event.detail.mode));

  // --- Palette ------------------------------------------------------------
  // A separate axis from light and dark: the palette picks the colour family,
  // the theme still decides day or night. Stored under its own namespaced key
  // and read back by theme-init.js before first paint.
  const PALETTES = ['azure', 'mono', 'ember'];
  const paletteInputs = document.querySelectorAll('input[name="palette"]');

  const readPalette = () => {
    try {
      const stored = localStorage.getItem('brightcv:palette');
      return PALETTES.includes(stored) ? stored : 'azure';
    } catch (error) {
      return 'azure';
    }
  };

  const writePalette = (value) => {
    try {
      localStorage.setItem('brightcv:palette', value);
    } catch (error) {
      /* Storage blocked: the choice applies for this page only. */
    }
  };

  if (paletteInputs.length) {
    const current = readPalette();
    paletteInputs.forEach((input) => {
      input.checked = input.value === current;
    });

    paletteInputs.forEach((input) => {
      input.addEventListener('change', () => {
        if (!input.checked || !PALETTES.includes(input.value)) return;
        document.documentElement.setAttribute('data-palette', input.value);
        writePalette(input.value);
        const label = input.closest('.theme-option')?.querySelector('b')?.textContent || input.value;
        window.Lunetti?.toast?.(`Colour set to ${label}.`);
      });
    });
  }
})();
