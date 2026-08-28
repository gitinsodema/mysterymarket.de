<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/db.php';

mmStartSecureSession();

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
if (!preg_match('/^[A-Z0-9-]{4,64}$/', $code)) {
    http_response_code(404);
    exit;
}

$verifiedAt = (int)($_SESSION['mm_verified_records'][$code] ?? 0);
if ($verifiedAt < time() - 900) {
    http_response_code(403);
    echo 'Please verify this reference again before opening the print card.';
    exit;
}

$stmt = mmDb()->prepare(
    'SELECT reference_code, person_name, role_label, agency_name, project_name, brand_name,
            valid_until, photo_asset, brand_logo_asset, agency_logo_asset, print_card_enabled
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

$verifyUrl = 'https://mysterymarket.de/verify.php?code=' . rawurlencode($code);

header('X-Robots-Tag: noindex, noarchive');
header('Cache-Control: private, no-store, max-age=0');
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
.id-card{width:var(--card-w);height:var(--card-h);background:white;border-radius:3.2mm;overflow:hidden;box-shadow:0 8px 30px #00195020;display:grid;grid-template-columns:28mm 1fr;position:relative}
.id-left{background:#001950;color:white;padding:4.2mm 3.6mm;display:flex;flex-direction:column;gap:2.5mm}
.id-brand{font-size:3.3mm;font-weight:800;line-height:1.05}
.id-brand small{display:block;font-size:1.7mm;font-weight:500;opacity:.8;margin-top:1mm}
.id-photo{width:20mm;height:25mm;border-radius:1.8mm;object-fit:cover;background:#e8edf4;border:0.4mm solid #ffffff55}
.id-photo-placeholder{width:20mm;height:25mm;border-radius:1.8mm;background:#ffffff16;border:0.4mm solid #ffffff55;display:grid;place-items:center;text-align:center;font-size:1.8mm;padding:1.5mm}
.id-left-bottom{margin-top:auto;font-size:1.6mm;opacity:.8}
.id-main{padding:4mm 4.3mm 3.4mm;display:grid;grid-template-columns:1fr 22mm;gap:3mm}
.id-kicker{text-transform:uppercase;letter-spacing:.08em;font-size:1.7mm;font-weight:800;color:#008c96}
.id-name{font-size:5.2mm;line-height:1;margin:1.4mm 0 .8mm}
.id-role{font-size:2.4mm;font-weight:800;color:#be001e;margin:0 0 2.4mm}
.id-meta{display:grid;gap:1.45mm;font-size:1.85mm;line-height:1.25}
.id-meta span{display:block;color:#667085;font-size:1.45mm;text-transform:uppercase;letter-spacing:.05em}
.id-meta strong{display:block}
.id-logo{display:block;max-width:20mm;max-height:8mm;object-fit:contain;object-position:left center;margin-bottom:1.6mm}
.id-qrcol{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.4mm}
#id-qr{width:20mm;height:20mm;background:white}
#id-qr img,#id-qr canvas{width:20mm!important;height:20mm!important;display:block}
.id-ref{font-size:1.65mm;font-weight:800;text-align:center}
.id-scan{font-size:1.35mm;color:#667085;text-align:center;line-height:1.25}
.print-controls{margin-top:18px;display:flex;gap:10px;justify-content:center}
@media print{
  @page{size:85.6mm 53.98mm;margin:0}
  html,body{width:85.6mm;height:53.98mm;background:white}
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
        <div class="id-brand">MysteryMarket<small>Audit & Field Services</small></div>
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
          <div class="id-meta">
            <div><span>Agency Partner</span><strong><?= mmEscape((string)$row['agency_name']) ?></strong></div>
            <div><span>Project</span><strong><?= mmEscape((string)$row['project_name']) ?></strong></div>
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
      <a class="button secondary" href="/verify.php?code=<?= rawurlencode($code) ?>">Back to Verify</a>
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
