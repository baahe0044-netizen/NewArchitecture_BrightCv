(() => {
  'use strict';
  const payload = JSON.parse(document.getElementById('printData').textContent);
  const printResume = document.getElementById('printResume');
  const printSheetWrap = document.getElementById('printSheetWrap');
  printResume.innerHTML = window.LunettiResume.renderResume(payload.resume, { placeholders: false });

  function fitPreview() {
    const pageWidth = 794;
    const available = Math.max(280, Math.min(pageWidth, window.innerWidth - 24));
    const scale = Math.min(1, available / pageWidth);
    const pageHeight = printResume.scrollHeight || 1123;
    printResume.style.setProperty('--print-preview-scale', String(scale));
    printSheetWrap.style.width = Math.round(pageWidth * scale) + 'px';
    printSheetWrap.style.height = Math.round(pageHeight * scale) + 'px';
    printSheetWrap.style.minHeight = '0';
  }

  requestAnimationFrame(fitPreview);
  // Fonts and the rendered CV can change the height after the first frame, so
  // fit again once everything has loaded.
  window.addEventListener('load', fitPreview);
  window.addEventListener('resize', fitPreview);
  window.addEventListener('orientationchange', fitPreview);

  document.getElementById('printNowButton').addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    try {
      await window.Lunetti.api(payload.exportEndpoint, {
        method: 'POST',
        body: JSON.stringify({ format: 'pdf' }),
      });
    } catch {
      // Printing remains available even if export analytics cannot be recorded.
    }
    button.disabled = false;
    button.removeAttribute('aria-busy');
    window.print();
  });
})();
