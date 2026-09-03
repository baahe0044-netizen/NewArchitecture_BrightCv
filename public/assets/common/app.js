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
      toast.className = 'toast ' + (type === 'error' ? 'error' : type === 'success' ? 'success' : '');
      toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
      toast.textContent = message;
      region.appendChild(toast);

      const stillMoving = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      const LIFETIME = 4200;
      let timer = null;

      const dismiss = () => {
        if (!toast.isConnected) return;
        // Reduced motion still removes the toast, it just does not animate it
        // away: the feedback is the same, only the movement is gone.
        if (!stillMoving) {
          toast.remove();
          return;
        }
        toast.classList.add('is-leaving');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
        // A missed animationend must not leave the toast on screen forever.
        setTimeout(() => toast.remove(), 600);
      };

      const start = () => {
        clearTimeout(timer);
        timer = setTimeout(dismiss, LIFETIME);
      };
      const hold = () => clearTimeout(timer);

      // Someone reading or tabbing through a message should not have it taken
      // away mid-sentence.
      toast.addEventListener('mouseenter', hold);
      toast.addEventListener('mouseleave', start);
      toast.addEventListener('focusin', hold);
      toast.addEventListener('focusout', start);

      start();
      return toast;
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

  // Theme. The stored value is the single source of truth for both the header
  // toggle and the Appearance settings page, so the two stay in sync.
  const THEME_KEY = 'brightcv-theme';
  const THEME_MODES = ['system', 'light', 'dark'];
  const THEME_LABELS = { system: 'System', light: 'Light', dark: 'Dark' };

  window.BrightTheme = {
    get() {
      try {
        const stored = localStorage.getItem(THEME_KEY);
        return stored === 'light' || stored === 'dark' ? stored : 'system';
      } catch {
        return 'system';
      }
    },
    apply(mode) {
      const root = document.documentElement;
      if (mode === 'light' || mode === 'dark') {
        root.setAttribute('data-theme', mode);
      } else {
        root.removeAttribute('data-theme');
      }
      root.setAttribute('data-theme-mode', mode);
    },
    set(mode) {
      if (!THEME_MODES.includes(mode)) return;
      this.apply(mode);
      try {
        localStorage.setItem(THEME_KEY, mode);
      } catch {
        /* Theme still applies for this page view, it just will not persist. */
      }
      document.dispatchEvent(new CustomEvent('brightcv:themechange', { detail: { mode } }));
    },
    label(mode) {
      return THEME_LABELS[mode] || 'System';
    },
  };

  const themeToggles = document.querySelectorAll('[data-theme-toggle]');
  const nextMode = (mode) => THEME_MODES[(THEME_MODES.indexOf(mode) + 1) % THEME_MODES.length];

  const describeToggles = (mode) => {
    const label = `Theme: ${window.BrightTheme.label(mode)}. Switch to ${window.BrightTheme.label(nextMode(mode))}.`;
    themeToggles.forEach((toggle) => {
      toggle.setAttribute('aria-label', label);
      toggle.setAttribute('title', label);
    });
  };

  if (themeToggles.length) {
    describeToggles(window.BrightTheme.get());
    themeToggles.forEach((toggle) => {
      toggle.addEventListener('click', () => {
        const next = nextMode(window.BrightTheme.get());
        window.BrightTheme.set(next);
        window.Lunetti.toast(`Theme: ${window.BrightTheme.label(next)}`);
      });
    });
  }

  document.addEventListener('brightcv:themechange', (event) => describeToggles(event.detail.mode));

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
