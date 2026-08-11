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

  // Theme. The stored value is the source of truth for both the header
  // toggle and the Appearance settings page, so they stay in sync.
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

  const setMenu = (open) => {
    nav?.classList.toggle('open', open);
    menuButton?.setAttribute('aria-expanded', String(open));
  };

  menuButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    setMenu(!nav?.classList.contains('open'));
  });

  document.addEventListener('click', (event) => {
    const close = event.target.closest('[data-modal-close]');
    if (close) window.Lunetti.closeModal(close.dataset.modalClose);
    if (event.target.classList.contains('modal')) {
      window.Lunetti.closeModal(event.target.id);
    }

    // Tapping anywhere outside the open mobile menu should dismiss it.
    if (nav?.classList.contains('open') && !event.target.closest('.app-nav')) {
      setMenu(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      document.querySelectorAll('.modal.open').forEach((modal) => window.Lunetti.closeModal(modal.id));
      if (nav?.classList.contains('open')) {
        setMenu(false);
        menuButton?.focus();
      }
    }
  });
})();
