(() => {
  'use strict';

  // Builds an A4 PDF of a CV in the browser and hands it straight to the
  // viewer as a file, without going through the print dialog. The two
  // libraries it needs are ~560 KB between them, so they are only fetched
  // the first time someone actually asks for a download.

  const PAGE_WIDTH = 794; // 210mm at 96 CSS px per inch
  const PAGE_HEIGHT = 1123; // 297mm
  const PAGE_PADDING = 57; // 15mm, the space kept at a page break
  const SCALE = 2;
  // Anything that should never be sliced in half across two pages.
  const KEEP_TOGETHER = '.cv-entry, .cv-compact-entry, .cv-reference, .cv-section h2, h1, h3, p, li, span, a, img';

  let librariesLoading = null;

  function loadScript(src) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = src;
      script.onload = resolve;
      script.onerror = () => reject(new Error('Could not load ' + src));
      document.head.appendChild(script);
    });
  }

  function loadLibraries(urls) {
    if (window.html2canvas && window.jspdf) return Promise.resolve();
    if (!librariesLoading) {
      librariesLoading = Promise.all(urls.map(loadScript)).catch((error) => {
        librariesLoading = null;
        throw error;
      });
    }
    return librariesLoading;
  }

  function filenameFor(name) {
    const base = String(name || 'cv').trim().replace(/[^\p{L}\p{N}_-]+/gu, '-').replace(/^-+|-+$/g, '').slice(0, 80);
    return (base || 'cv') + '.pdf';
  }

  // Choose where each page ends: as close to a full page as possible, but
  // moved up to the top of any line, entry, or heading the cut would split.
  function pageBreaks(documentElement, totalHeight) {
    const origin = documentElement.getBoundingClientRect().top;
    const blocks = Array.from(documentElement.querySelectorAll(KEEP_TOGETHER)).map((element) => {
      const rect = element.getBoundingClientRect();
      let bottom = rect.bottom - origin;
      // A heading carries the first line after it along to the next page.
      if (element.tagName === 'H2') bottom += 40;
      return { top: rect.top - origin, bottom };
    }).filter((block) => block.bottom > block.top);

    const breaks = [];
    let start = 0;
    while (start < totalHeight) {
      const usable = PAGE_HEIGHT - (breaks.length ? PAGE_PADDING : 0);
      if (totalHeight - start <= usable) break;

      const ideal = start + usable - PAGE_PADDING;
      const floor = start + usable * 0.5;
      let cut = ideal;
      let moved = true;
      while (moved) {
        moved = false;
        for (const block of blocks) {
          if (block.top < cut && block.bottom > cut && block.top > floor) {
            cut = block.top - 4;
            moved = true;
          }
        }
      }
      breaks.push(cut);
      start = cut;
    }
    return breaks;
  }

  async function renderPdf(resume, options) {
    await loadLibraries(options.libraries);

    const host = document.createElement('div');
    host.setAttribute('aria-hidden', 'true');
    host.style.cssText = 'position:absolute;top:0;left:-20000px;width:' + PAGE_WIDTH + 'px;pointer-events:none;';
    host.innerHTML = window.LunettiResume.renderResume(resume, { placeholders: false });
    document.body.appendChild(host);

    try {
      const sheet = host.querySelector('.cv-document') || host.firstElementChild;
      // The on-screen sheet keeps a full A4 minimum height; a longer CV is
      // paginated below instead.
      sheet.style.minHeight = PAGE_HEIGHT + 'px';
      sheet.style.boxShadow = 'none';
      if (document.fonts && document.fonts.ready) await document.fonts.ready;

      const totalHeight = Math.ceil(sheet.scrollHeight);
      const breaks = pageBreaks(sheet, totalHeight);
      const canvas = await window.html2canvas(sheet, {
        scale: SCALE,
        backgroundColor: '#ffffff',
        useCORS: true,
        logging: false,
        width: PAGE_WIDTH,
        height: totalHeight,
        windowWidth: PAGE_WIDTH,
        scrollX: 0,
        scrollY: 0,
      });

      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({ unit: 'px', format: [PAGE_WIDTH, PAGE_HEIGHT], orientation: 'portrait', hotfixes: ['px_scaling'], compress: true });
      pdf.setProperties({ title: resume.name || 'CV', creator: options.creator || '' });

      const page = document.createElement('canvas');
      page.width = PAGE_WIDTH * SCALE;
      page.height = PAGE_HEIGHT * SCALE;
      const context = page.getContext('2d');

      const edges = [0, ...breaks, totalHeight];
      for (let index = 0; index < edges.length - 1; index += 1) {
        const from = edges[index] * SCALE;
        const to = Math.min(edges[index + 1], totalHeight) * SCALE;
        const offset = index === 0 ? 0 : PAGE_PADDING * SCALE;
        const sliceHeight = to - from;

        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, page.width, page.height);
        // Stretch the pixel row at each cut across the page padding, so a
        // coloured sidebar or band runs on to the edge of the paper rather
        // than stopping in a white strip.
        if (offset) context.drawImage(canvas, 0, from, canvas.width, 1, 0, 0, page.width, offset);
        context.drawImage(canvas, 0, from, canvas.width, sliceHeight, 0, offset, page.width, sliceHeight);
        const rest = page.height - offset - sliceHeight;
        if (rest > 0) context.drawImage(canvas, 0, Math.max(0, to - 1), canvas.width, 1, 0, offset + sliceHeight, page.width, rest);

        if (index > 0) pdf.addPage([PAGE_WIDTH, PAGE_HEIGHT], 'portrait');
        pdf.addImage(page.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, PAGE_WIDTH, PAGE_HEIGHT, undefined, 'FAST');
      }

      pdf.save(filenameFor(resume.name));
    } finally {
      host.remove();
    }
  }

  // Wires a button up to download, with a busy state while the PDF is built.
  async function download(button, resume, options) {
    const original = button ? button.innerHTML : '';
    if (button) {
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.innerHTML = '<span class="spinner"></span> Preparing…';
    }
    try {
      await renderPdf(resume, options);
      return true;
    } catch (error) {
      console.error(error);
      window.Lunetti?.toast?.('The PDF could not be created. Try Print instead.', 'error');
      return false;
    } finally {
      if (button) {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.innerHTML = original;
      }
    }
  }

  window.LunettiDownload = { download };
})();
