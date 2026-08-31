(() => {
  const standalone =
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true;

  if (standalone) {
    document.documentElement.classList.add('backoffice-standalone');
  }

  // This Backoffice intentionally has no offline service worker.
  // Remove an earlier experimental registration if it exists.
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(registrations => {
      registrations.forEach(registration => {
        if (registration.scope.includes('/backoffice/')) {
          registration.unregister().catch(() => {});
        }
      });
    }).catch(() => {});
  }

  const hint = document.querySelector('[data-backoffice-install-hint]');
  if (!hint || standalone) {
    return;
  }

  const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
  if (isIos) {
    hint.hidden = false;
  }
})();
