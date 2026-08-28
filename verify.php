<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/db.php';

$c = mmPageCopy('verify');
$result = null;
$error = null;
$code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));
    if ($code === '' || strlen($code) > 64) {
        $error = $c['invalid'];
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
                $error = $c['missing'];
            }
        } catch (Throwable $e) {
            $error = $c['unavailable'];
        }
    }
}

mmHeader($c['title'], $c['lead']);
?>
<section class="hero"><div><p class="eyebrow">Audit Verification</p><h1><?= mmEscape($c['hero']) ?></h1><p class="lead"><?= mmEscape($c['lead']) ?></p></div></section>
<section class="section">
  <div class="verify-box">
    <?php if ($error): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <?php if ($result): ?>
      <div class="alert success">
        <strong><?= mmEscape($c['verified']) ?></strong>
        <p><?= mmEscape((string)$result['public_title']) ?></p>
        <?php if (($result['confidentiality_mode'] ?? '') === 'confidential'): ?>
          <p><?= mmEscape($c['conf']) ?></p>
        <?php else: ?>
          <?php if (!empty($result['public_partner'])): ?><p><strong><?= mmEscape($c['partner']) ?>:</strong> <?= mmEscape((string)$result['public_partner']) ?></p><?php endif; ?>
          <?php if (!empty($result['public_client'])): ?><p><strong><?= mmEscape($c['client']) ?>:</strong> <?= mmEscape((string)$result['public_client']) ?></p><?php endif; ?>
        <?php endif; ?>
        <?php if (!empty($result['valid_from']) || !empty($result['valid_until'])): ?><p><strong><?= mmEscape($c['valid']) ?>:</strong> <?= mmEscape((string)($result['valid_from'] ?: '—')) ?> – <?= mmEscape((string)($result['valid_until'] ?: '—')) ?></p><?php endif; ?>
        <?php if (!empty($result['public_note'])): ?><p><?= mmEscape((string)$result['public_note']) ?></p><?php endif; ?>
      </div>
    <?php endif; ?>
    <form method="post">
      <label><?= mmEscape($c['label']) ?>
        <input name="code" maxlength="64" autocomplete="off" placeholder="MM-26-XXXX" value="<?= mmEscape($code) ?>" required>
      </label>
      <button type="submit"><?= mmEscape($c['button']) ?></button>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
