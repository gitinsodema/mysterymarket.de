<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/db.php';

$result = null;
$error = null;
$code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));
    if ($code === '' || strlen($code) > 64) {
        $error = 'Bitte geben Sie eine gültige Verifikationsreferenz ein.';
    } else {
        try {
            $stmt = mmDb()->prepare(
                'SELECT reference_code, public_title, public_partner, public_client, valid_from, valid_until, confidentiality_mode, public_note
                 FROM audit_verifications
                 WHERE reference_code = :code AND is_active = 1
                 LIMIT 1'
            );
            $stmt->execute(['code' => $code]);
            $row = $stmt->fetch();
            if ($row) {
                $result = $row;
            } else {
                $error = 'Für diese Referenz wurde kein aktiver Verifikationseintrag gefunden.';
            }
        } catch (Throwable $e) {
            $error = 'Die Verifikation ist derzeit nicht verfügbar. Bitte kontaktieren Sie MysteryMarket direkt.';
        }
    }
}

mmHeader('Audit Verification', 'Legitimation und Audit-Referenzen von MysteryMarket prüfen.');
?>
<section class="hero"><div><p class="eyebrow">Audit Verification</p><h1>Audit oder Legitimation prüfen.</h1><p class="lead">Sie wurden wegen eines Audits kontaktiert oder haben einen Auditor vor Ort? Prüfen Sie hier die angegebene MysteryMarket-Referenz.</p></div></section>
<section class="section">
  <div class="verify-box">
    <?php if ($error): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <?php if ($result): ?>
      <div class="alert success">
        <strong>Verifiziert · aktiver Vorgang</strong>
        <p><?= mmEscape((string)$result['public_title']) ?></p>
        <?php if (($result['confidentiality_mode'] ?? '') === 'confidential'): ?>
          <p>Dieses Auditprogramm ist vertraulich. Auftraggeber- und Projektdetails werden öffentlich nicht angezeigt.</p>
        <?php else: ?>
          <?php if (!empty($result['public_partner'])): ?><p><strong>Audit Partner:</strong> <?= mmEscape((string)$result['public_partner']) ?></p><?php endif; ?>
          <?php if (!empty($result['public_client'])): ?><p><strong>Kunde:</strong> <?= mmEscape((string)$result['public_client']) ?></p><?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($result['valid_from']) || !empty($result['valid_until'])): ?><p><strong>Gültigkeit:</strong> <?= mmEscape((string)($result['valid_from'] ?: '—')) ?> bis <?= mmEscape((string)($result['valid_until'] ?: '—')) ?></p><?php endif; ?>
        <?php if (!empty($result['public_note'])): ?><p><?= mmEscape((string)$result['public_note']) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>
    <form method="post">
      <label>Verification Code / Referenznummer
        <input name="code" maxlength="64" autocomplete="off" placeholder="z. B. MM-26-XXXX" value="<?= mmEscape($code) ?>" required>
      </label>
      <button type="submit">Verifizieren</button>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
