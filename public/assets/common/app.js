(() => {
  'use strict';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const baseUrl = document.querySelector('meta[name="app-url"]')?.content?.replace(/\/$/, '') || '';

  window.Lunetti = {
    baseUrl,
    csrf,
    async api(path, options = {}) {
      const headers = new Headers(options.headers || {});
      headers.set('Accept', 'application/json');
      headers.set('X-CSRF-Token', csrf);
      if (options.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
      }

      const response = await fetch(baseUrl + path, {
        credentials: 'same-origin',
        ...options,
        headers,
      });
      let payload;
      try {
        payload = await response.json();
      } catch {
        payload = { success: false, message: 'The server returned an unreadable response.' };
      }

      if (!response.ok || payload.success === false) {
        const error = new Error(payload.message || 'Request failed.');
        error.status = response.status;
        error.payload = payload;
        throw error;
      }
      return payload;
    },
    toast(message, type = 'success') {
      let region = document.querySelector('.toast-region');
      if (!region) {
        region = document.createElement('div');
        region.className = 'toast-region';
        region.setAttribute('aria-live', 'polite');
        document.body.appendChild(region);
      }
      const toast = document.createElement('div');
      toast.className = 'toast ' + (type === 'error' ? 'error' : '');
      toast.textContent = message;
      region.appendChild(toast);
      setTimeout(() => toast.remove(), 3600);
    },
    openModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      modal.querySelector('input, button, textarea, select')?.focus();
    },
    closeModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
    },
  };

  const menuButton = document.querySelector('[data-mobile-menu]');
  const nav = document.querySelector('.app-nav');
  menuButton?.addEventListener('click', () => {
    const open = nav?.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(Boolean(open)));
  });

  document.addEventListener('click', (event) => {
    const close = event.target.closest('[data-modal-close]');
    if (close) window.Lunetti.closeModal(close.dataset.modalClose);
    if (event.target.classList.contains('modal')) {
      window.Lunetti.closeModal(event.target.id);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal.open').forEach((modal) => window.Lunetti.closeModal(modal.id));
    }
  });
})();
