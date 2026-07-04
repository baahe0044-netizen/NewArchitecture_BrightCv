
    const BASE_URL = (function() {
      const url = new URL(window.location.href);
      const p = url.searchParams;
      if (!p.get('resume_id')) p.set('resume_id', '<?php echo (int)$resumeId; ?>');
      p.delete('action');
      p.delete('template_id');
      return url.pathname + '?' + p.toString();
    })();

    /* ── Category filter ── */
    document.querySelectorAll('.filter-tab').forEach(tab => {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => {
          t.classList.remove('active');
          t.setAttribute('aria-selected', 'false');
        });
        this.classList.add('active');
        this.setAttribute('aria-selected', 'true');

        const cat = this.dataset.category;
        document.querySelectorAll('.template-card').forEach(card => {
          card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
        });
      });
    });

    /* ── Preview ── */
    function previewTemplate(templateId, templateName) {
      const url = `Templates/template_preview.php?template_id=${encodeURIComponent(templateId)}`;
      const panel = document.getElementById('inlinePreview');
      const frame = document.getElementById('inlinePreviewFrame');
      const title = document.getElementById('inlinePreviewTitle');
      const opener = document.getElementById('inlinePreviewOpen');

      frame.src = url;
      title.textContent = templateName;
      opener.onclick = () => window.open(url, '_blank', 'noopener');

      panel.style.display = 'block';
      panel.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }

    document.getElementById('inlinePreviewClose').addEventListener('click', () => {
      const panel = document.getElementById('inlinePreview');
      document.getElementById('inlinePreviewFrame').src = 'about:blank';
      panel.style.display = 'none';
    });

    /* ── Select template ── */
    async function selectTemplate(templateId, templateName) {
      const form = new FormData();
      form.append('action', 'use');
      form.append('template_id', String(templateId));
      form.append('resume_id', '<?php echo (int)$resumeId; ?>');

      try {
        const resp = await fetch(BASE_URL, {
          method: 'POST',
          body: form,
          credentials: 'same-origin'
        });
        const data = await resp.json();
        if (resp.ok && data && data.ok) {
          window.location.href = `index.php?page=resume/builder&resume_id=${data.resume_id}&template_id=${data.template_id}`;
        } else {
          alert('Could not save template selection: ' + (data?.error || 'Unknown error'));
        }
      } catch (err) {
        alert('Network error: ' + (err?.message || ''));
      }
    }

    /* ── Lazy-load iframe thumbnails ── */
    (function() {
      const frames = document.querySelectorAll('.half-frame');
      if (!('IntersectionObserver' in window)) {
        frames.forEach(f => {
          if (!f.src || f.src === window.location.href) f.src = f.dataset.src;
        });
        return;
      }
      const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            const f = e.target;
            if (!f.src || f.src === window.location.href) f.src = f.dataset.src;
            obs.unobserve(f);
          }
        });
      }, {
        rootMargin: '200px',
        threshold: 0.01
      });
      frames.forEach(f => io.observe(f));
    })();
  