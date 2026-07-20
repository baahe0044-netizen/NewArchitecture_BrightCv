(() => {
  'use strict';
  const payload = JSON.parse(document.getElementById('printData').textContent);
  document.getElementById('printResume').innerHTML = window.LunettiResume.renderResume(payload.resume, { placeholders: false });

  document.getElementById('printNowButton').addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    try {
      await window.Lunetti.api(payload.exportEndpoint, {
        method: 'POST',
        body: JSON.stringify({ format: 'pdf' }),
      });
    } catch {
      // Printing remains available even if export analytics cannot be recorded.
    }
    button.disabled = false;
    window.print();
  });
})();
