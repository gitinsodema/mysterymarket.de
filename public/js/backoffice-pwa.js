(() => {
  if (!('serviceWorker' in navigator)) {
    return;
  }

  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/backoffice-sw.js', { scope: '/backoffice/' }).catch(() => {});
  });

  const standalone =
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true;

  if (standalone) {
    document.documentElement.classList.add('backoffice-standalone');
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
