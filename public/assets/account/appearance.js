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
})();
