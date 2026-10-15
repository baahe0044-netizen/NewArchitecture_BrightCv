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

  const paletteInputs = document.querySelectorAll('input[name="palette"]');
  const palette = window.BrightPalette;
  if (paletteInputs.length && palette) {
    const syncPaletteInputs = (value) => {
      paletteInputs.forEach((input) => {
        input.checked = input.value === value;
      });
    };

    syncPaletteInputs(palette.get());

    paletteInputs.forEach((input) => {
      input.addEventListener('change', () => {
        if (!input.checked) return;
        palette.set(input.value);
        window.Lunetti?.toast?.(`Accent set to ${palette.label(input.value)}.`);
      });
    });

    document.addEventListener('brightcv:palettechange', (event) => syncPaletteInputs(event.detail.palette));
  }
})();
