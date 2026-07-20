(() => {
  'use strict';
  let pendingDeleteId = null;

  document.querySelectorAll('[data-create-resume]').forEach((button) => {
    button.addEventListener('click', () => {
      window.Lunetti.openModal('createResumeModal');
      setTimeout(() => document.getElementById('resumeName')?.focus(), 50);
    });
  });

  document.getElementById('createResumeForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button[type="submit"]');
    const name = new FormData(form).get('name')?.toString().trim();
    if (!name) return;

    button.disabled = true;
    const label = button.textContent;
    button.innerHTML = '<span class="spinner"></span> Creating…';
    try {
      const response = await window.Lunetti.api('/api/resumes', {
        method: 'POST',
        body: JSON.stringify({ name }),
      });
      window.location.href = response.data.builder_url;
    } catch (error) {
      window.Lunetti.toast(error.message, 'error');
      button.disabled = false;
      button.textContent = label;
    }
  });

  document.addEventListener('click', async (event) => {
    const menuButton = event.target.closest('[data-resume-menu]');
    const insideMenu = event.target.closest('.resume-menu');
    const targetMenu = menuButton
      ? document.querySelector('[data-menu-for="' + menuButton.dataset.resumeMenu + '"]')
      : null;
    const shouldOpen = Boolean(targetMenu && !targetMenu.classList.contains('open'));
    document.querySelectorAll('.resume-menu.open').forEach((menu) => {
      if (!insideMenu || menu !== insideMenu) {
        menu.classList.remove('open');
        document.querySelector('[data-resume-menu="' + menu.dataset.menuFor + '"]')?.setAttribute('aria-expanded', 'false');
      }
    });
    if (menuButton) {
      targetMenu?.classList.toggle('open', shouldOpen);
      menuButton.setAttribute('aria-expanded', String(shouldOpen));
      return;
    }

    const duplicate = event.target.closest('[data-duplicate]');
    if (duplicate) {
      duplicate.disabled = true;
      try {
        const response = await window.Lunetti.api('/api/resumes/' + duplicate.dataset.duplicate + '/duplicate', {
          method: 'POST',
          body: JSON.stringify({}),
        });
        window.location.href = response.data.builder_url;
      } catch (error) {
        window.Lunetti.toast(error.message, 'error');
        duplicate.disabled = false;
      }
    }

    const remove = event.target.closest('[data-delete]');
    if (remove) {
      pendingDeleteId = remove.dataset.delete;
      remove.closest('.resume-menu')?.classList.remove('open');
      document.querySelector('[data-resume-menu="' + pendingDeleteId + '"]')?.setAttribute('aria-expanded', 'false');
      document.getElementById('deleteResumeMessage').textContent =
        '“' + (remove.dataset.name || 'This CV') + '” will no longer appear in your workspace.';
      window.Lunetti.openModal('deleteResumeModal');
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.resume-menu.open').forEach((menu) => {
      menu.classList.remove('open');
      const trigger = document.querySelector('[data-resume-menu="' + menu.dataset.menuFor + '"]');
      trigger?.setAttribute('aria-expanded', 'false');
      trigger?.focus();
    });
  });

  document.getElementById('confirmDeleteResume')?.addEventListener('click', async (event) => {
    if (!pendingDeleteId) return;
    const button = event.currentTarget;
    button.disabled = true;
    try {
      await window.Lunetti.api('/api/resumes/' + pendingDeleteId, {
        method: 'DELETE',
        body: JSON.stringify({}),
      });
      document.querySelector('[data-resume-card="' + pendingDeleteId + '"]')?.remove();
      window.Lunetti.closeModal('deleteResumeModal');
      window.Lunetti.toast('CV moved to trash.');
      pendingDeleteId = null;
    } catch (error) {
      window.Lunetti.toast(error.message, 'error');
    } finally {
      button.disabled = false;
    }
  });
})();
