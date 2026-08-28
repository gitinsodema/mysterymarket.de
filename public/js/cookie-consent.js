(() => {
  const key = 'mm_cookie_choice_v1';
  const panel = document.querySelector('[data-cookie-panel]');
  if (!panel) return;

  let returnFocus = null;

  const show = (trigger = null) => {
    returnFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
    panel.hidden = false;
    panel.setAttribute('aria-hidden', 'false');
    requestAnimationFrame(() => {
      panel.querySelector('button, a[href], input, select, textarea')?.focus();
    });
  };

  const hide = () => {
    panel.hidden = true;
    panel.setAttribute('aria-hidden', 'true');
    if (returnFocus instanceof HTMLElement) {
      returnFocus.focus();
    }
  };

  panel.setAttribute('aria-hidden', panel.hidden ? 'true' : 'false');

  if (!localStorage.getItem(key)) show();

  document.querySelectorAll('[data-cookie-settings]').forEach(el => {
    el.addEventListener('click', event => {
      event.preventDefault();
      show(el);
    });
  });

  panel.querySelector('[data-cookie-necessary]')?.addEventListener('click', () => {
    localStorage.setItem(key, 'necessary');
    hide();
  });

  panel.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
    localStorage.setItem(key, 'accepted');
    hide();
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !panel.hidden) hide();
  });
})();
