<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/credentials.php';

header('X-Robots-Tag: noindex, noarchive');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET','HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit;
}

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
if (!preg_match('/^[A-Z0-9-]{4,64}$/', $code)) {
    http_response_code(404);
    exit;
}

$user = mmBackofficeRequireLogin();

$stmt = mmDb()->prepare(
    'SELECT reference_code, person_name, role_label, agency_name, project_name, brand_name,
            valid_until, photo_asset, brand_logo_asset, agency_logo_asset, print_card_enabled, photo_allowed, subject_user_id
     FROM audit_verifications
     WHERE reference_code = :code
       AND is_active = 1
       AND print_card_enabled = 1
       AND is_personal_verification = 1
       AND (valid_from IS NULL OR valid_from <= CURRENT_DATE())
       AND (valid_until IS NULL OR valid_until >= CURRENT_DATE())
     LIMIT 1'
);
$stmt->execute(['code' => $code]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit;
}

if (!mmCredentialUserCanAccess($user, $row)) {
    http_response_code(403);
    exit('Forbidden');
}

$verifyUrl = 'https://mysterymarket.de/verify?code=' . rawurlencode($code) . '#credential';

if ($method === 'HEAD') {
    exit;
}
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Auditor ID · <?= mmEscape((string)$row['person_name']) ?></title>
<link rel="stylesheet" href="/public/css/style.css">
<style>
:root{--card-w:85.6mm;--card-h:53.98mm}
body{margin:0;background:#eef2f6;font-family:Arial,sans-serif;color:#001950}
.card-print-shell{min-height:100vh;display:grid;place-items:center;padding:24px}
.id-card{width:var(--card-w);height:var(--card-h);background:white;border-radius:3.2mm;overflow:hidden;box-shadow:0 8px 30px #00195020;display:grid;grid-template-columns:24.5mm 1fr;position:relative}
.id-left{background:#001950;color:white;padding:3.8mm 3.1mm;display:flex;flex-direction:column;gap:2.1mm}
.id-brand{font-size:2.75mm;font-weight:800;line-height:.96;letter-spacing:-.01em}
.id-brand span{display:block}.id-brand small{display:block;font-size:1.55mm;font-weight:500;opacity:.8;margin-top:.9mm;line-height:1.15}
.id-photo{width:18.5mm;height:23mm;border-radius:1.6mm;object-fit:cover;background:#e8edf4;border:0.35mm solid #ffffff55}
.id-photo-placeholder{width:18.5mm;height:23mm;border-radius:1.6mm;background:#ffffff16;border:0.35mm solid #ffffff55;display:grid;place-items:center;text-align:center;font-size:1.65mm;padding:1.2mm}
.id-left-bottom{margin-top:auto;font-size:1.6mm;opacity:.8}
.id-main{padding:3.6mm 4mm 3.2mm;display:grid;grid-template-columns:minmax(0,1fr) 19mm;gap:2.6mm}
.id-kicker{text-transform:uppercase;letter-spacing:.08em;font-size:1.55mm;font-weight:800;color:#008c96;line-height:1.2}
.id-name{font-size:5.1mm;line-height:.95;margin:1.1mm 0 .6mm}
.id-role{font-size:2.2mm;font-weight:800;color:#be001e;margin:0 0 1.2mm}
.id-photo-permission{display:inline-flex;align-items:center;gap:.75mm;margin:0 0 1.4mm;padding:.75mm 1.15mm;border-radius:1.2mm;background:#e8f7ef;border:.25mm solid #8ed3b0;color:#08784f;font-size:1.45mm;font-weight:900;letter-spacing:.02em;line-height:1}
.id-photo-permission svg{width:2.35mm;height:2.35mm;flex:0 0 auto}
.id-meta{display:grid;gap:1.05mm;font-size:1.72mm;line-height:1.18}
.id-meta span{display:block;color:#667085;font-size:1.28mm;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2mm}
.id-meta strong{display:block}.id-project strong{line-height:1.12;word-break:normal;overflow-wrap:anywhere}
.id-logo{display:block;max-width:18mm;max-height:7mm;object-fit:contain;object-position:left center;margin-bottom:1.35mm}
.id-agency-logo{display:block;max-width:14mm;max-height:4.2mm;object-fit:contain;object-position:left center;margin-top:.55mm;opacity:.82}
.id-qrcol{display:flex;flex-direction:column;align-items:center;justify-content:flex-start;gap:1.05mm;padding-top:7.2mm}
#id-qr{width:18mm;height:18mm;background:white}
#id-qr img,#id-qr canvas{width:18mm!important;height:18mm!important;display:block}
.id-ref{font-size:1.5mm;font-weight:800;text-align:center;line-height:1.1}
.id-scan{font-size:1.2mm;color:#667085;text-align:center;line-height:1.15}
.print-controls{margin-top:18px;display:flex;gap:10px;justify-content:center}
@media print{
  @page{size:85.6mm 53.98mm;margin:0}
  html,body{width:85.6mm;height:53.98mm;background:white;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .card-print-shell{display:block;min-height:0;padding:0}
  .id-card{box-shadow:none;border-radius:0;width:85.6mm;height:53.98mm}
  .print-controls{display:none!important}
}
</style>
</head>
<body>
<div class="card-print-shell">
  <div>
    <section class="id-card">
      <div class="id-left">
        <div class="id-brand"><span>Mystery</span><span>Market</span><small>Audit & Field Services</small></div>
        <?php if (!empty($row['photo_asset'])): ?>
          <img class="id-photo" src="/verify-asset.php?code=<?= rawurlencode($code) ?>&type=photo" alt="">
        <?php else: ?>
          <div class="id-photo-placeholder">PHOTO<br>TO BE ADDED</div>
        <?php endif; ?>
        <div class="id-left-bottom">Independent Field Auditor</div>
      </div>
      <div class="id-main">
        <div>
          <?php if (!empty($row['brand_logo_asset'])): ?>
            <img class="id-logo" src="/verify-asset.php?code=<?= rawurlencode($code) ?>&type=brand_logo" alt="">
          <?php endif; ?>
          <div class="id-kicker">Audit / Authorisation Verification</div>
          <h1 class="id-name"><?= mmEscape((string)$row['person_name']) ?></h1>
          <p class="id-role"><?= mmEscape((string)$row['role_label']) ?></p>
          <?php if ((int)($row['photo_allowed'] ?? 0) === 1): ?>
            <div class="id-photo-permission" title="Fotografieren im Projektkontext erlaubt">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 4l1.4-2h3.2L15 4h3a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h3zm3 3a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 2.2A2.8 2.8 0 1 1 12 14.8 2.8 2.8 0 0 1 12 9.2z"/></svg>
              FOTO ERLAUBT
            </div>
          <?php endif; ?>
          <div class="id-meta">
            <div><span>Agency Partner</span><strong><?= mmEscape((string)$row['agency_name']) ?></strong><?php if (!empty($row['agency_logo_asset'])): ?><img class="id-agency-logo" src="/verify-asset.php?code=<?= rawurlencode($code) ?>&type=agency_logo" alt=""><?php endif; ?></div>
            <div class="id-project"><span>Project</span><strong><?= mmEscape((string)$row['project_name']) ?></strong></div>
            <div><span>Valid until</span><strong><?= mmEscape((string)$row['valid_until']) ?></strong></div>
          </div>
        </div>
        <div class="id-qrcol">
          <div id="id-qr" data-url="<?= mmEscape($verifyUrl) ?>"></div>
          <div class="id-ref"><?= mmEscape($code) ?></div>
          <div class="id-scan">Scan to verify<br>authorisation</div>
        </div>
      </div>
    </section>
    <div class="print-controls">
      <button type="button" onclick="window.print()">Print card</button>
      <a class="button secondary" href="<?= ($user['role'] ?? '') === 'elite' ? '/backoffice/profile.php' : '/backoffice/credentials.php' ?>">Back to Backoffice</a>
    </div>
  </div>
</div>
<script src="/public/vendor/qrcodejs/qrcode.min.js"></script>
<script>
const qrEl = document.getElementById('id-qr');
if (qrEl && window.QRCode) {
  new QRCode(qrEl, {
    text: qrEl.dataset.url,
    width: 260,
    height: 260,
    colorDark: '#001950',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.H
  });
}
</script>
</body>
</html>
