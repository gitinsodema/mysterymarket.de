import QrScanner from '/public/vendor/qr-scanner/qr-scanner.min.js';

const root = document.querySelector('[data-verify-scanner]');
if (!root) {
  throw new Error('Verify scanner root missing.');
}

const video = root.querySelector('[data-verify-video]');
const wrap = root.querySelector('[data-verify-video-wrap]');
const status = root.querySelector('[data-verify-scan-status]');
const startButton = root.querySelector('[data-verify-scan-start]');
const stopButton = root.querySelector('[data-verify-scan-stop]');
const form = document.querySelector('.verify-box form');
const input = form?.querySelector('input[name="code"]');

let scanner = null;

const setStatus = (message, state = '') => {
  status.textContent = message || '';
  status.dataset.state = state;
};

const extractReference = (payload) => {
  const value = String(payload || '').trim();
  if (!value) return '';

  const direct = value.toUpperCase();
  if (/^[A-Z0-9-]{4,64}$/.test(direct)) {
    return direct;
  }

  try {
    const url = new URL(value);
    const host = url.hostname.toLowerCase();
    if (host !== 'mysterymarket.de' && host !== 'www.mysterymarket.de') {
      return '';
    }
    if (url.pathname !== '/verify.php' && url.pathname !== '/verify') {
      return '';
    }

    const candidate = (
      url.searchParams.get('code') ||
      url.searchParams.get('reference') ||
      url.searchParams.get('ref') ||
      ''
    ).trim().toUpperCase();

    return /^[A-Z0-9-]{4,64}$/.test(candidate) ? candidate : '';
  } catch {
    return '';
  }
};

const stopScanner = async () => {
  if (scanner) {
    await scanner.stop();
    scanner.destroy();
    scanner = null;
  }
  wrap.hidden = true;
  startButton.hidden = false;
  stopButton.hidden = true;
};

const handleResult = async (result) => {
  const reference = extractReference(result?.data ?? result);
  if (!reference) {
    setStatus(root.dataset.invalid, 'error');
    return;
  }

  if (!input || !form) {
    setStatus(root.dataset.invalid, 'error');
    return;
  }

  input.value = reference;
  setStatus(root.dataset.found, 'success');
  await stopScanner();
  form.requestSubmit();
};

startButton.addEventListener('click', async () => {
  if (!video || !navigator.mediaDevices?.getUserMedia) {
    setStatus(root.dataset.camera, 'error');
    return;
  }

  try {
    setStatus(root.dataset.ready, 'ready');
    wrap.hidden = false;
    startButton.hidden = true;
    stopButton.hidden = false;

    scanner = new QrScanner(video, handleResult, {
      preferredCamera: 'environment',
      highlightScanRegion: true,
      highlightCodeOutline: true,
      returnDetailedScanResult: true,
      maxScansPerSecond: 12,
    });

    await scanner.start();
  } catch (error) {
    await stopScanner();
    setStatus(root.dataset.camera, 'error');
  }
});

stopButton.addEventListener('click', async () => {
  await stopScanner();
  setStatus('');
});

window.addEventListener('pagehide', () => {
  if (scanner) {
    scanner.destroy();
    scanner = null;
  }
});
