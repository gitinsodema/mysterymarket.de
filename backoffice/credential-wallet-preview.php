<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$outputId = (int)($_GET['output_id'] ?? 0);
if ($outputId < 1) {
    http_response_code(404);
    exit;
}

$stmt = mmDb()->prepare(
    "SELECT o.id AS output_id, o.output_status, o.output_type, v.*
     FROM verify_credential_outputs o
     JOIN audit_verifications v ON v.id = o.audit_verification_id
     WHERE o.id = :id
       AND o.output_type = 'apple_wallet'
     LIMIT 1"
);
$stmt->execute(['id'=>$outputId]);
$credential = $stmt->fetch();

if (!$credential) {
    http_response_code(404);
    exit;
}

$payload = mmAppleWalletPassPayload($credential);
$readiness = mmAppleWalletReadiness();
$barcode = $payload['barcodes'][0] ?? [];
$generic = $payload['generic'] ?? [];

mmHeader('Wallet Vorschau', 'Technische Vorschau des Apple Wallet Pass-Mappings.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Apple Wallet · technische Vorschau</p>
    <h1><?= mmEscape((string)$credential['project_name']) ?></h1>
    <p class="lead"><?= mmEscape((string)$credential['reference_code']) ?> · keine .pkpass-Datei, keine Signatur-Simulation</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/credential-output.php?id=<?= $outputId ?>">Zurück zur Ausgabeanfrage</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="wallet-preview-card">
      <div class="wallet-preview-top">
        <span><?= mmEscape((string)($payload['logoText'] ?? 'MysteryMarket')) ?></span>
        <small>VERIFY</small>
      </div>

      <div class="wallet-preview-primary">
        <small>PROJEKT</small>
        <strong><?= mmEscape((string)$credential['project_name']) ?></strong>
      </div>

      <div class="wallet-preview-fields">
        <span><small>PERSON</small><strong><?= mmEscape((string)$credential['person_name']) ?></strong></span>
        <span><small>AGENTUR</small><strong><?= mmEscape((string)$credential['agency_name']) ?></strong></span>
        <span><small>GÜLTIG BIS</small><strong><?= mmEscape((string)$credential['valid_until']) ?></strong></span>
        <span><small>VERIFY</small><strong><?= mmEscape((string)$credential['reference_code']) ?></strong></span>
      </div>

      <div class="wallet-preview-qr" data-wallet-preview-qr data-wallet-message="<?= mmEscape((string)($barcode['message'] ?? '')) ?>">
        <div data-wallet-qr-target></div>
        <small><?= mmEscape((string)$credential['reference_code']) ?></small>
      </div>
    </article>

    <article class="card">
      <span class="badge">Readiness</span>
      <h3>Signaturstatus</h3>
      <?php if (!empty($readiness['ready'])): ?>
        <p><?= mmBackofficeStatusBadge('active', 'bereit') ?></p>
      <?php else: ?>
        <p><?= mmBackofficeStatusBadge('pending', 'Setup offen') ?></p>
        <ul class="credential-wallet-issues">
          <?php foreach ($readiness['issues'] as $issue): ?><li><?= mmEscape((string)$issue) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <p class="partner-note">Diese Seite zeigt ausschließlich das Daten-Mapping. Sie erzeugt absichtlich keinen unsigned oder simulierten Apple-Pass.</p>
    </article>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <div class="section-head">
      <p class="eyebrow">Pass-Mapping</p>
      <h2>Technische Felder.</h2>
    </div>

    <div class="credential-wallet-mapping">
      <div><strong>Pass-Typ</strong><span>Generic Pass</span></div>
      <div><strong>Serial Number</strong><span><?= mmEscape((string)($payload['serialNumber'] ?? '')) ?></span></div>
      <div><strong>Pass Type Identifier</strong><span><?= mmEscape((string)($payload['passTypeIdentifier'] ?: 'noch nicht konfiguriert')) ?></span></div>
      <div><strong>Team Identifier</strong><span><?= mmEscape((string)($payload['teamIdentifier'] ?: 'noch nicht konfiguriert')) ?></span></div>
      <div><strong>Expiration Date</strong><span><?= mmEscape((string)($payload['expirationDate'] ?? '—')) ?></span></div>
      <div><strong>Relevant Date</strong><span><?= mmEscape((string)($payload['relevantDate'] ?? '—')) ?></span></div>
      <div class="wide"><strong>QR / Verify URL</strong><span><?= mmEscape((string)($barcode['message'] ?? '')) ?></span></div>
    </div>
  </div>
</section>

<script src="/public/vendor/qrcodejs/qrcode.min.js"></script>
<script>
(() => {
  const box = document.querySelector('[data-wallet-preview-qr]');
  const target = box?.querySelector('[data-wallet-qr-target]');
  const message = box?.getAttribute('data-wallet-message') || '';
  if (!box || !target || !message || typeof QRCode === 'undefined') return;

  new QRCode(target, {
    text: message,
    width: 132,
    height: 132,
    correctLevel: QRCode.CorrectLevel.M
  });
})();
</script>
<?php mmFooter(); ?>
