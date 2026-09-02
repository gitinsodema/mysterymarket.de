<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

$personalStmt = mmDb()->query(
    "SELECT id, reference_code, public_title, public_partner, public_client,
            person_name, role_label, agency_name, project_name, brand_name,
            valid_from, valid_until, print_card_enabled, is_personal_verification,
            is_active
     FROM audit_verifications
     WHERE is_personal_verification = 1
     ORDER BY is_active DESC, valid_until DESC, id DESC"
);
$credentials = $personalStmt->fetchAll();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $verificationId = (int)($_POST['audit_verification_id'] ?? 0);
        $outputType = (string)($_POST['output_type'] ?? '');
        $allowed = [
            'apple_wallet',
            'physical_card',
            'transparent_holder',
            'mysterymarket_lanyard',
            'elite_shopper_lanyard',
            'full_set',
            'replacement_card',
        ];

        if ($verificationId < 1 || !in_array($outputType, $allowed, true)) {
            $error = 'Ungültige Ausgabeanfrage.';
        } else {
            $check = mmDb()->prepare(
                'SELECT id, reference_code, is_personal_verification
                 FROM audit_verifications
                 WHERE id = :id
                 LIMIT 1'
            );
            $check->execute(['id'=>$verificationId]);
            $credential = $check->fetch();

            if (!$credential || (int)$credential['is_personal_verification'] !== 1) {
                $error = 'Persönlicher Verify-Ausweis nicht gefunden.';
            } else {
                $duplicate = mmDb()->prepare(
                    "SELECT COUNT(*)
                     FROM verify_credential_outputs
                     WHERE audit_verification_id = :verification_id
                       AND output_type = :output_type
                       AND output_status IN ('requested','approved','processing','ready')"
                );
                $duplicate->execute([
                    'verification_id'=>$verificationId,
                    'output_type'=>$outputType,
                ]);

                if ((int)$duplicate->fetchColumn() > 0) {
                    $error = 'Für diesen Ausweis und Ausgabeweg existiert bereits eine offene Anfrage.';
                } else {
                    $insert = mmDb()->prepare(
                        "INSERT INTO verify_credential_outputs
                         (audit_verification_id, requested_by_user_id, output_type, output_status, requested_at, created_at, updated_at)
                         VALUES (:verification_id, :requested_by, :output_type, 'requested', NOW(), NOW(), NOW())"
                    );
                    $insert->execute([
                        'verification_id'=>$verificationId,
                        'requested_by'=>(int)$user['id'],
                        'output_type'=>$outputType,
                    ]);
                    $outputId = (int)mmDb()->lastInsertId();
                    mmBackofficeAudit(
                        (int)$user['id'],
                        'verify_credential_output.requested',
                        'verify_credential_output',
                        $outputId,
                        ['audit_verification_id'=>$verificationId,'output_type'=>$outputType]
                    );
                    header('Location: /backoffice/credentials.php?requested=1', true, 303);
                    exit;
                }
            }
        }
    }
}

$outputStmt = mmDb()->query(
    "SELECT o.*, v.reference_code, v.person_name, v.brand_name, v.agency_name
     FROM verify_credential_outputs o
     JOIN audit_verifications v ON v.id = o.audit_verification_id
     ORDER BY o.requested_at DESC, o.id DESC
     LIMIT 100"
);
$outputs = $outputStmt->fetchAll();
$walletReadiness = mmAppleWalletReadiness();

mmHeader('Credentials', 'Verify-Ausweisservice für projektbezogene Audit Credentials.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Credential Service</p>
    <h1>Verify-Ausweise.</h1>
    <p class="lead">Projektbezogene Ausweise aus bestehenden Verify-Datensätzen erstellen, prüfen, drucken und künftig in Apple Wallet ausgeben.</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/">Dashboard</a>
      <a class="button secondary" href="/backoffice/credential-projects.php">Projekt-Stammdaten</a>
      <a class="button secondary" href="/backoffice/card-calibration.php">CR80 kalibrieren</a>
    </div>
  </div>
</section>

<section class="section">
  <?php if (isset($_GET['requested'])): ?><div class="alert success"><strong>Ausgabeanfrage gespeichert.</strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="section-head credential-service-heading">
    <div>
      <p class="eyebrow">Bestehende Credentials</p>
      <h2>Verify ist die Quelle.</h2>
      <p>Jeder Eintrag entspricht einem realen projektbezogenen Verify-Ausweis. Keine zusätzliche MysteryMarket-Identität wird erzeugt.</p>
    </div>
    <div class="credential-service-actions">
      <a class="button secondary" href="/backoffice/credential-projects.php">Projekte verwalten</a>
      <a class="button" href="/backoffice/credential-new.php">Neuen Ausweis anlegen</a>
    </div>
  </div>

  <?php if (!$credentials): ?>
    <div class="notice">Noch keine persönlichen Verify-Ausweise vorhanden.</div>
  <?php else: ?>
    <div class="credential-certificate-list">
      <?php foreach ($credentials as $credential): ?>
        <?php $state = mmCredentialVerifyState($credential); ?>
        <article class="credential-certificate" data-credential-certificate>
          <div class="credential-certificate-summary">
            <div class="credential-certificate-mark">ID</div>
            <div class="credential-certificate-main">
              <div class="credential-certificate-topline">
                <span class="badge"><?= mmEscape(mmCredentialProjectLabel($credential)) ?></span>
                <?= mmBackofficeStatusBadge($state) ?>
              </div>
              <h3><?= mmEscape((string)($credential['project_name'] ?: $credential['public_title'] ?: 'Projekt-Ausweis')) ?></h3>
              <div class="credential-certificate-meta">
                <span><strong><?= mmEscape((string)($credential['person_name'] ?: '—')) ?></strong><small>Person</small></span>
                <span><strong><?= mmEscape((string)($credential['agency_name'] ?: '—')) ?></strong><small>Agentur</small></span>
                <span><strong><?= mmEscape((string)$credential['reference_code']) ?></strong><small>Verify-Referenz</small></span>
                <span><strong><?= mmEscape((string)($credential['valid_until'] ?: 'offen')) ?></strong><small>Gültig bis</small></span>
              </div>
            </div>
            <button type="button"
                    class="button secondary credential-certificate-toggle"
                    data-credential-toggle
                    aria-expanded="false">
              Ausweis öffnen
            </button>
          </div>

          <div class="credential-certificate-details" data-credential-details hidden>
            <div class="credential-certificate-detail-head">
              <div>
                <span class="eyebrow">Ausweisfunktionen</span>
                <h4><?= mmEscape((string)$credential['reference_code']) ?></h4>
              </div>
              <div class="credential-service-actions">
                <a class="button" href="/backoffice/credential.php?id=<?= (int)$credential['id'] ?>">Ausweis verwalten</a>
                <a class="button secondary" href="/verify?code=<?= rawurlencode((string)$credential['reference_code']) ?>">Verify öffnen</a>
                <?php if ((int)$credential['print_card_enabled'] === 1): ?>
                  <a class="button secondary" href="/verify-card.php?code=<?= rawurlencode((string)$credential['reference_code']) ?>">Druckkarte anzeigen</a>
                <?php endif; ?>
              </div>
            </div>

            <div class="credential-output-section">
              <div class="credential-output-section-head">
                <strong>Ausgabe & Ausstattung</strong>
                <small>Alle Ausgabewege beziehen sich auf genau diesen Verify-Ausweis.</small>
              </div>
              <div class="credential-output-grid">
                <?php
                $options = [
                    'apple_wallet' => ['Apple Wallet','Digitaler Pass'],
                    'physical_card' => ['Physische Karte','CR80-Karte'],
                    'transparent_holder' => ['Halter','Transparenter Halter'],
                    'mysterymarket_lanyard' => ['MM Lanyard','MysteryMarket'],
                    'elite_shopper_lanyard' => ['Elite Lanyard','Elite Shopper'],
                    'full_set' => ['Komplettset','Karte + Halter + Lanyard'],
                    'replacement_card' => ['Ersatzkarte','Verlust / Beschädigung'],
                ];
                foreach ($options as $type=>[$label,$copy]):
                ?>
                  <form method="post" action="/backoffice/credentials.php" class="credential-output-option">
                    <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
                    <input type="hidden" name="audit_verification_id" value="<?= (int)$credential['id'] ?>">
                    <input type="hidden" name="output_type" value="<?= mmEscape($type) ?>">
                    <div>
                      <strong><?= mmEscape($label) ?></strong>
                      <small><?= mmEscape($copy) ?></small>
                    </div>
                    <button type="submit" class="button secondary">Auswählen</button>
                  </form>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="section">
  <div class="section-head credential-service-heading">
    <div>
      <p class="eyebrow">Ausgabe / Fulfilment</p>
      <h2>Anfragen.</h2>
    </div>
    <div class="credential-wallet-readiness">
      <strong>Apple Wallet</strong>
      <?= !empty($walletReadiness['ready'])
          ? mmBackofficeStatusBadge('active', 'bereit')
          : mmBackofficeStatusBadge('pending', 'Setup offen') ?>
    </div>
  </div>

  <?php if (!$outputs): ?>
    <p class="partner-note">Noch keine Ausgabeanfragen.</p>
  <?php else: ?>
    <div class="backoffice-list">
      <?php foreach ($outputs as $output): ?>
        <article>
          <div>
            <strong><?= mmEscape((string)$output['reference_code']) ?> · <?= mmEscape(mmCredentialOutputLabel((string)$output['output_type'])) ?></strong>
            <small><?= mmEscape((string)$output['person_name']) ?> · <?= mmEscape(mmCredentialProjectLabel($output)) ?> · <?= mmEscape((string)$output['requested_at']) ?></small>
          </div>
          <div class="credential-output-list-actions">
            <?= mmBackofficeStatusBadge((string)$output['output_status']) ?>
            <a class="button secondary" href="/backoffice/credential-output.php?id=<?= (int)$output['id'] ?>">Bearbeiten</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php mmFooter(); ?>
