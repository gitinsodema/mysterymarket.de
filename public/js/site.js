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
})();
