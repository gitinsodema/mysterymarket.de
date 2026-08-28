(() => {
  const key = 'mm_cookie_choice_v1';
  const panel = document.querySelector('[data-cookie-panel]');
  if (!panel) return;

  const show = () => { panel.hidden = false; };
  const hide = () => { panel.hidden = true; };

  if (!localStorage.getItem(key)) show();

  document.querySelectorAll('[data-cookie-settings]').forEach(el => {
    el.addEventListener('click', show);
  });

  panel.querySelector('[data-cookie-necessary]')?.addEventListener('click', () => {
    localStorage.setItem(key, 'necessary');
    hide();
  });

  panel.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
    localStorage.setItem(key, 'accepted');
    hide();
  });
})();
