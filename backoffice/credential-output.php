<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

/*
 * Printer-independent credential output workflow.
 *
 * Output requests reference audit_verifications.id and never create a second
 * identity. Physical fulfilment is vendor-neutral; device-specific printer
 * integration is intentionally deferred until hardware is selected.
 *
 * Apple Wallet is treated as a digital output and can only enter processing
 * when the real signing configuration passes mmAppleWalletReadiness().
 */

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit;
}

function mmCredentialOutputFetch(int $id): ?array
{
    $stmt = mmDb()->prepare(
        "SELECT o.*, v.reference_code, v.person_name, v.role_label, v.agency_name,
                v.project_name, v.brand_name, v.valid_from, v.valid_until, v.is_active
         FROM verify_credential_outputs o
         JOIN audit_verifications v ON v.id = o.audit_verification_id
         WHERE o.id = :id
         LIMIT 1"
    );
    $stmt->execute(['id'=>$id]);
    return $stmt->fetch() ?: null;
}

$output = mmCredentialOutputFetch($id);
if (!$output) {
    http_response_code(404);
    exit;
}

$error = '';
$walletReadiness = mmAppleWalletReadiness();
$isWallet = (string)$output['output_type'] === 'apple_wallet';
$isPhysical = mmCredentialOutputIsPhysical((string)$output['output_type']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $current = (string)$output['output_status'];

        try {
            $allowedTransitions = [
                'requested'=>['approved','cancelled'],
                'approved'=>['processing','cancelled'],
                'processing'=>['ready','cancelled'],
                'ready'=>['shipped','cancelled'],
                'shipped'=>[],
                'cancelled'=>[],
            ];

            if ($action === 'set_status') {
                $next = (string)($_POST['output_status'] ?? '');
                if (!in_array($next, $allowedTransitions[$current] ?? [], true)) {
                    throw new RuntimeException('Dieser Statuswechsel ist nicht erlaubt.');
                }

                if ($isWallet && in_array($next, ['processing','ready'], true) && empty($walletReadiness['ready'])) {
                    throw new RuntimeException('Apple Wallet ist technisch noch nicht bereit: ' . implode(' ', $walletReadiness['issues']));
                }

                if ($next === 'shipped' && !$isPhysical) {
                    throw new RuntimeException('Nur physische Ausgaben können als versendet markiert werden.');
                }

                $shippingReference = trim((string)($_POST['shipping_reference'] ?? ''));
                if ($next === 'shipped' && $shippingReference === '') {
                    throw new RuntimeException('Für den Versand ist eine Versandreferenz erforderlich.');
                }
                if (mb_strlen($shippingReference) > 120) {
                    throw new RuntimeException('Versandreferenz ist zu lang.');
                }

                $set = ['output_status = :status','updated_at = NOW()'];
                $params = ['status'=>$next,'id'=>$id];

                if ($next === 'approved') {
                    $set[] = 'approved_at = COALESCE(approved_at, NOW())';
                }
                if (in_array($next, ['processing','ready'], true)) {
                    $set[] = 'processed_at = COALESCE(processed_at, NOW())';
                }
                if ($next === 'shipped') {
                    $set[] = 'shipped_at = NOW()';
                    $set[] = 'shipping_reference = :shipping_reference';
                    $params['shipping_reference'] = $shippingReference;
                }

                $stmt = mmDb()->prepare(
                    'UPDATE verify_credential_outputs
                     SET ' . implode(', ', $set) . '
                     WHERE id = :id'
                );
                $stmt->execute($params);

                mmBackofficeAudit((int)$user['id'], 'verify_credential_output.status_changed', 'verify_credential_output', $id, [
                    'from'=>$current,
                    'to'=>$next,
                    'reference_code'=>(string)$output['reference_code']
                ]);

                header('Location: /backoffice/credential-output.php?id=' . $id . '&saved=1', true, 303);
                exit;
            }

            throw new InvalidArgumentException('Unbekannte Aktion.');
        } catch (InvalidArgumentException|RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Die Ausgabeanfrage konnte nicht aktualisiert werden.';
        }
    }
}

$output = mmCredentialOutputFetch($id);
$current = (string)$output['output_status'];
$walletReadiness = mmAppleWalletReadiness();
$isWallet = (string)$output['output_type'] === 'apple_wallet';
$isPhysical = mmCredentialOutputIsPhysical((string)$output['output_type']);

$nextOptions = match ($current) {
    'requested'=>['approved'=>'Freigeben','cancelled'=>'Stornieren'],
    'approved'=>['processing'=>'In Bearbeitung','cancelled'=>'Stornieren'],
    'processing'=>['ready'=>'Bereit','cancelled'=>'Stornieren'],
    'ready'=>$isPhysical ? ['shipped'=>'Versendet','cancelled'=>'Stornieren'] : [],
    default=>[],
};

mmHeader('Ausgabeanfrage', 'Verify-Ausweis Ausgabe und Fulfilment verwalten.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Ausgabe / Fulfilment</p>
    <h1><?= mmEscape(mmCredentialOutputLabel((string)$output['output_type'])) ?></h1>
    <p class="lead"><?= mmEscape((string)$output['reference_code']) ?> · <?= mmEscape((string)$output['person_name']) ?></p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/credentials.php">Zurück zum Ausweis-Service</a>
      <a class="button secondary" href="/backoffice/credential.php?id=<?= (int)$output['audit_verification_id'] ?>">Ausweis verwalten</a>
    </div>
  </div>
</section>

<section class="section">
  <?php if (isset($_GET['saved'])): ?><div class="alert success"><strong>Status aktualisiert.</strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="grid two">
    <article class="card">
      <span class="badge">Ausgabeanfrage</span>
      <h2><?= mmEscape((string)$output['reference_code']) ?></h2>
      <p><?= mmBackofficeStatusBadge($current) ?></p>
      <p><strong><?= mmEscape((string)$output['person_name']) ?></strong><br><?= mmEscape((string)$output['project_name']) ?></p>
      <p><?= mmEscape((string)$output['brand_name']) ?> · <?= mmEscape((string)$output['agency_name']) ?></p>
      <p><strong>Angefragt:</strong> <?= mmEscape((string)$output['requested_at']) ?></p>
      <?php if (!empty($output['shipping_reference'])): ?><p><strong>Versand:</strong> <?= mmEscape((string)$output['shipping_reference']) ?></p><?php endif; ?>
    </article>

    <article class="card">
      <span class="badge"><?= $isWallet ? 'Digital' : 'Fulfilment' ?></span>
      <?php if ($isWallet): ?>
        <h3>Apple Wallet Bereitschaft</h3>
        <?php if (!empty($walletReadiness['ready'])): ?>
          <p><?= mmBackofficeStatusBadge('active', 'bereit') ?></p>
          <p>Pass Type ID, Team ID, Artwork und Signaturkette sind serverseitig vorhanden.</p>
          <?php if (in_array($current, ['processing','ready'], true) && (int)$output['is_active'] === 1): ?>
            <div class="actions">
              <a class="button" href="/backoffice/credential-wallet.php?output_id=<?= $id ?>">Wallet-Pass erzeugen</a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <p><?= mmBackofficeStatusBadge('pending', 'noch nicht bereit') ?></p>
          <ul class="credential-wallet-issues">
            <?php foreach ($walletReadiness['issues'] as $issue): ?><li><?= mmEscape((string)$issue) ?></li><?php endforeach; ?>
          </ul>
          <p class="partner-note">Es wird noch keine .pkpass-Datei erzeugt. Die Anfrage kann freigegeben werden; Bearbeitung/Bereitstellung bleibt bis zur echten Apple-Konfiguration blockiert.</p>
        <?php endif; ?>
      <?php else: ?>
        <h3>Physische Ausgabe</h3>
        <p>Diese Anfrage läuft über den operativen Druck-/Pack-/Versandprozess.</p>
        <p class="partner-note">CR80-Druckkarte und Verify bleiben dieselbe Credential-Quelle; Fulfilment erzeugt keine neue Identität.</p>
      <?php endif; ?>
    </article>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <div class="section-head">
      <p class="eyebrow">Workflow</p>
      <h2>Status bearbeiten.</h2>
    </div>

    <?php if (!$nextOptions): ?>
      <div class="notice">Für den aktuellen Status gibt es keine weitere Aktion.</div>
    <?php else: ?>
      <?php foreach ($nextOptions as $status=>$label): ?>
        <form method="post" action="/backoffice/credential-output.php?id=<?= $id ?>" class="credential-output-transition">
          <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="set_status">
          <input type="hidden" name="output_status" value="<?= mmEscape($status) ?>">
          <div>
            <strong><?= mmEscape($label) ?></strong>
            <small><?= mmEscape($current) ?> → <?= mmEscape($status) ?></small>
          </div>
          <?php if ($status === 'shipped'): ?>
            <input name="shipping_reference" maxlength="120" required placeholder="Versandreferenz / Tracking">
          <?php endif; ?>
          <button type="submit"<?= $status === 'cancelled' ? ' class="button secondary"' : '' ?>><?= mmEscape($label) ?></button>
        </form>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php mmFooter(); ?>
