(() => {
  'use strict';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const baseUrl = document.querySelector('meta[name="app-url"]')?.content?.replace(/\/$/, '') || '';
  const modalFocus = new WeakMap();

  const focusableElements = (container) => Array.from(container.querySelectorAll(
    'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
  )).filter((element) => !element.hidden && element.getAttribute('aria-hidden') !== 'true');

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
      toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
      toast.textContent = message;
      region.appendChild(toast);
      setTimeout(() => toast.remove(), 3600);
    },
    openModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      modalFocus.set(modal, document.activeElement);
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      modal.setAttribute('aria-modal', 'true');
      if (!modal.hasAttribute('role')) modal.setAttribute('role', 'dialog');
      document.body.classList.add('modal-open');
      const preferredFocus = modal.querySelector('[autofocus], input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), select:not([disabled])');
      (preferredFocus || focusableElements(modal)[0])?.focus();
    },
    closeModal(id) {
      const modal = document.getElementById(id);
      if (!modal) return;
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      if (!document.querySelector('.modal.open')) document.body.classList.remove('modal-open');
      const priorFocus = modalFocus.get(modal);
      if (priorFocus instanceof HTMLElement && priorFocus.isConnected) priorFocus.focus();
      modalFocus.delete(modal);
    },
  };

  const menuButton = document.querySelector('[data-mobile-menu]');
  const nav = document.querySelector('.app-nav');
  const closeMenu = () => {
    nav?.classList.remove('open');
    menuButton?.setAttribute('aria-expanded', 'false');
    menuButton?.setAttribute('aria-label', 'Open navigation menu');
  };

  menuButton?.addEventListener('click', () => {
    const open = nav?.classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(Boolean(open)));
    menuButton.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
  });

  document.addEventListener('click', (event) => {
    if (nav?.classList.contains('open') && !nav.contains(event.target) && !menuButton?.contains(event.target)) {
      closeMenu();
    }
    if (event.target.closest('.app-nav a')) closeMenu();

    const close = event.target.closest('[data-modal-close]');
    if (close) window.Lunetti.closeModal(close.dataset.modalClose);
    if (event.target.classList.contains('modal')) {
      window.Lunetti.closeModal(event.target.id);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      const openModal = document.querySelector('.modal.open');
      if (openModal) {
        window.Lunetti.closeModal(openModal.id);
      } else if (nav?.classList.contains('open')) {
        closeMenu();
        menuButton?.focus();
      }
    }

    if (event.key === 'Tab') {
      const openModal = document.querySelector('.modal.open');
      if (!openModal) return;
      const items = focusableElements(openModal);
      if (!items.length) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 760) closeMenu();
  });
})();
