(function () {
  'use strict';

  // Draws a filled example CV into every [data-cv-thumb="template-key"]
  // element, using the same renderer as the builder, and scales the A4 page
  // down to the element's width. Thumbnails are drawn as they come near the
  // screen, so a page with all eighteen does not render them all at once.

  const PAGE_WIDTH = 794; // 210mm in CSS pixels

  function fit(thumb) {
    const page = thumb.querySelector('.cv-thumb-page');
    if (!page) return;
    page.style.setProperty('--thumb-scale', String(thumb.clientWidth / PAGE_WIDTH));
  }

  const resizer = typeof ResizeObserver === 'function'
    ? new ResizeObserver((entries) => entries.forEach((entry) => fit(entry.target)))
    : null;

  function render(thumb) {
    if (!window.LunettiResume || !window.LunettiSample || thumb.dataset.rendered === 'true') return;
    const resume = window.LunettiSample.sampleResume(thumb.dataset.cvThumb, thumb.dataset.accent);
    const page = document.createElement('div');
    page.className = 'cv-thumb-page';
    page.setAttribute('aria-hidden', 'true');
    page.innerHTML = window.LunettiResume.renderResume(resume, { placeholders: false });
    thumb.replaceChildren(page);
    thumb.dataset.rendered = 'true';
    fit(thumb);
    resizer?.observe(thumb);
  }

  const watcher = typeof IntersectionObserver === 'function'
    ? new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        watcher.unobserve(entry.target);
        render(entry.target);
      });
    }, { rootMargin: '400px' })
    : null;

  function renderAll(scope) {
    (scope || document).querySelectorAll('[data-cv-thumb]').forEach((thumb) => {
      if (watcher) watcher.observe(thumb);
      else render(thumb);
    });
  }

  window.LunettiThumbs = { render, renderAll, fit };
  renderAll(document);
})();
