(() => {
  'use strict';

  const baseUrl = document.querySelector('meta[name="app-url"]')?.content?.replace(/\/$/, '') || '';

  // ------------------------------------------------------------------
  // Service worker
  // ------------------------------------------------------------------

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register(baseUrl + '/sw.js').catch(() => {
        // An install can fail on an insecure origin or with storage disabled.
        // The app works exactly as before without it, so this stays silent.
      });
    });

    // Signing out clears the caches so nothing of this person's session is
    // left on a shared device.
    document.addEventListener('submit', (event) => {
      const action = event.target?.getAttribute?.('action') || '';
      if (action.endsWith('/logout')) {
        navigator.serviceWorker.controller?.postMessage({ type: 'clear-caches' });
      }
    });
  }

  // ------------------------------------------------------------------
  // Install prompt
  //
  // Chrome and Edge fire beforeinstallprompt when the app qualifies. The event
  // is kept so the offer can be made from a real button instead of the browser
  // deciding when to show its own. iOS has no such event and installs through
  // the Share sheet, so it gets a short instruction instead.
  // ------------------------------------------------------------------

  const DISMISS_KEY = 'brightcv.install.dismissed';
  let deferredPrompt = null;

  const alreadyInstalled = () =>
    window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

  const dismissed = () => {
    try {
      return localStorage.getItem(DISMISS_KEY) === '1';
    } catch {
      return false;
    }
  };

  function buildBanner(message, actionLabel, onAction) {
    const banner = document.createElement('div');
    banner.className = 'install-banner';
    banner.setAttribute('role', 'region');
    banner.setAttribute('aria-label', 'Install BrightCV');

    const text = document.createElement('p');
    text.textContent = message;
    banner.appendChild(text);

    const actions = document.createElement('div');
    actions.className = 'install-banner-actions';

    if (onAction) {
      const install = document.createElement('button');
      install.type = 'button';
      install.className = 'btn btn-primary btn-small';
      install.textContent = actionLabel;
      install.addEventListener('click', () => onAction(banner));
      actions.appendChild(install);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'icon-btn install-banner-close';
    close.setAttribute('aria-label', 'Dismiss install prompt');
    close.textContent = '×';
    close.addEventListener('click', () => {
      try {
        localStorage.setItem(DISMISS_KEY, '1');
      } catch {
        // A private window cannot remember the choice; the banner simply
        // returns on the next visit.
      }
      banner.remove();
    });
    actions.appendChild(close);

    banner.appendChild(actions);
    document.body.appendChild(banner);
    return banner;
  }

  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    if (dismissed() || alreadyInstalled()) return;

    buildBanner('Install BrightCV for a full-screen app on this device.', 'Install', async (banner) => {
      banner.remove();
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
    });
  });

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    document.querySelector('.install-banner')?.remove();
  });

  // iOS Safari installs from the Share sheet and never fires the event above.
  const isIosSafari = /iPad|iPhone|iPod/.test(navigator.userAgent)
    && !/CriOS|FxiOS|EdgiOS/.test(navigator.userAgent);

  if (isIosSafari && !alreadyInstalled() && !dismissed()) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        buildBanner('Add BrightCV to your Home Screen: tap Share, then Add to Home Screen.', '', null);
      }, 2500);
    });
  }
})();
