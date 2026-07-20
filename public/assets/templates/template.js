(() => {
  'use strict';
  let activeFilter = 'all';
  let previewedKey = 'modern';
  let previewedName = 'Modern Focus';

  const cards = [...document.querySelectorAll('[data-template-card]')];
  const search = document.getElementById('templateSearch');

  function applyFilters() {
    const term = (search?.value || '').trim().toLowerCase();
    let visible = 0;
    cards.forEach((card) => {
      const matchesCategory = activeFilter === 'all' || card.dataset.category === activeFilter;
      const matchesSearch = !term || card.dataset.name.includes(term) || card.dataset.category.includes(term);
      const show = matchesCategory && matchesSearch;
      card.hidden = !show;
      if (show) visible++;
    });
    document.getElementById('templateEmpty').hidden = visible !== 0;
  }

  document.querySelectorAll('[data-template-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-template-filter]').forEach((item) => item.classList.remove('active'));
      button.classList.add('active');
      activeFilter = button.dataset.templateFilter;
      applyFilters();
    });
  });
  search?.addEventListener('input', applyFilters);

  function chooseTemplate(key, name) {
    document.getElementById('selectedTemplateKey').value = key;
    document.getElementById('nameTemplateResumeTitle').textContent = 'Create with ' + name;
    document.getElementById('templateResumeName').value = name + ' CV';
    window.Lunetti.closeModal('templatePreviewModal');
    window.Lunetti.openModal('nameTemplateResumeModal');
    setTimeout(() => document.getElementById('templateResumeName')?.select(), 50);
  }

  document.addEventListener('click', (event) => {
    const use = event.target.closest('[data-use-template]');
    if (use) chooseTemplate(use.dataset.useTemplate, use.dataset.templateName);

    const preview = event.target.closest('[data-preview-template]');
    if (preview) {
      previewedKey = preview.dataset.previewTemplate;
      previewedName = preview.dataset.templateName;
      const source = preview.closest('.template-preview');
      const clone = source.cloneNode(true);
      clone.querySelector('.template-overlay')?.remove();
      document.getElementById('largeTemplatePreview').replaceChildren(clone);
      document.getElementById('previewTemplateTitle').textContent = previewedName;
      window.Lunetti.openModal('templatePreviewModal');
    }
  });

  document.getElementById('usePreviewedTemplate')?.addEventListener('click', () => chooseTemplate(previewedKey, previewedName));

  document.getElementById('templateResumeForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = event.currentTarget.querySelector('button[type="submit"]');
    const name = document.getElementById('templateResumeName').value.trim();
    const templateKey = document.getElementById('selectedTemplateKey').value;
    if (!name) return;

    button.disabled = true;
    button.innerHTML = '<span class="spinner"></span> Creating…';
    try {
      const response = await window.Lunetti.api('/api/resumes', {
        method: 'POST',
        body: JSON.stringify({ name, template_key: templateKey }),
      });
      window.location.href = response.data.builder_url;
    } catch (error) {
      window.Lunetti.toast(error.message, 'error');
      button.disabled = false;
      button.textContent = 'Create and edit';
    }
  });
})();
