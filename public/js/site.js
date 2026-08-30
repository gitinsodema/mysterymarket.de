(() => {
  document.querySelectorAll('[data-mm-mail]').forEach((link) => {
    const local = String(link.dataset.mmLocal || '').split('').reverse().join('');
    const domain = String(link.dataset.mmDomain || '').split('').reverse().join('');
    if (!local || !domain) return;
    const address = local + '@' + domain;
    link.href = 'mailto:' + address;
    if (link.dataset.mmReveal === '1') {
      link.textContent = address;
    }
  });


  const activationForm = document.querySelector('[data-elite-activation-form]');
  if (activationForm) {
    const password = activationForm.querySelector('[data-elite-password]');
    const repeat = activationForm.querySelector('[data-elite-password-repeat]');
    const error = activationForm.querySelector('[data-elite-password-error]');

    const showError = (message) => {
      if (!error) return;
      error.textContent = message;
      error.hidden = false;
    };

    activationForm.addEventListener('submit', (event) => {
      if (!password || !repeat) return;
      if (password.value.length < 12) {
        event.preventDefault();
        showError('Das Passwort muss mindestens 12 Zeichen lang sein.');
        password.focus();
        return;
      }
      if (password.value !== repeat.value) {
        event.preventDefault();
        showError('Die Passwörter stimmen nicht überein.');
        repeat.focus();
        return;
      }
      if (error) error.hidden = true;
    });

    password?.addEventListener('input', () => {
      if (password.value.length > 0 && password.value.length < 12) {
        showError('Noch ' + (12 - password.value.length) + ' Zeichen bis zur Mindestlänge.');
      } else if (error) {
        error.hidden = true;
      }
    });
  }
})();
