<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit;
}

function mmCredentialAdminFetch(int $id): ?array
{
    $stmt = mmDb()->prepare(
        'SELECT *
         FROM audit_verifications
         WHERE id = :id
           AND is_personal_verification = 1
         LIMIT 1'
    );
    $stmt->execute(['id'=>$id]);
    return $stmt->fetch() ?: null;
}

$credential = mmCredentialAdminFetch($id);
if (!$credential) {
    http_response_code(404);
    exit;
}

$credentialSubjects = mmCredentialControlledSubjects();
$credentialRoles = mmCredentialControlledRoles();
$credentialProjects = mmCredentialControlledProjects();

$agencyOptions = [];
$customerOptions = [];
foreach ($credentialProjects as $projectOption) {
    $agencyOptions[(int)$projectOption['agency_id']] = (string)$projectOption['agency_name'];
    $customerOptions[(int)$projectOption['agency_id'] . '|' . (string)$projectOption['customer_name']] = [
        'agency_id'=>(int)$projectOption['agency_id'],
        'customer_name'=>(string)$projectOption['customer_name'],
    ];
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? 'save_details');

    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } elseif ((int)$credential['is_active'] === 1 && !in_array($action, ['deactivate','create_revision'], true)) {
        $error = 'Aktive Verify-Ausweise sind schreibgeschützt. Änderungen erfolgen über Deaktivierung oder einen kontrollierten Revisions-Draft.';
    } else {
        try {
            if ($action === 'set_subject') {
                $subjectUserId = (int)($_POST['subject_user_id'] ?? 0);
                if ($subjectUserId < 1) {
                    throw new InvalidArgumentException('Eine aktive Elite-Person muss ausgewählt werden.');
                }

                $checkSubject = mmDb()->prepare(
                    "SELECT u.id, m.display_name
                     FROM backoffice_users u
                     JOIN elite_members m ON m.user_id = u.id
                     WHERE u.id = :id
                       AND u.role = 'elite'
                       AND u.account_status = 'active'
                       AND m.membership_status = 'active'
                     LIMIT 1"
                );
                $checkSubject->execute(['id'=>$subjectUserId]);
                $subject = $checkSubject->fetch();
                if (!$subject || trim((string)$subject['display_name']) === '') {
                    throw new InvalidArgumentException('Die ausgewählte Ausweis-Person ist nicht als aktiver Elite Shopper verfügbar.');
                }

                $stmt = mmDb()->prepare(
                    'UPDATE audit_verifications
                     SET subject_user_id = :subject_user_id,
                         person_name = :person_name,
                         updated_at = NOW()
                     WHERE id = :id AND is_personal_verification = 1 AND is_active = 0'
                );
                $stmt->execute([
                    'subject_user_id'=>$subjectUserId,
                    'person_name'=>(string)$subject['display_name'],
                    'id'=>$id,
                ]);

                mmBackofficeAudit((int)$user['id'], 'verify_credential.subject_bound', 'audit_verification', $id, [
                    'reference_code'=>(string)$credential['reference_code'],
                    'subject_user_id'=>$subjectUserId,
                    'person_name'=>(string)$subject['display_name'],
                ]);
                header('Location: /backoffice/credential.php?id=' . $id . '&subject_saved=1', true, 303);
                exit;
            }

            if ($action === 'save_details') {
                $subjectUserId = (int)($credential['subject_user_id'] ?? 0);
                $roleId = (int)($_POST['credential_role_id'] ?? 0);
                $agencyId = (int)($_POST['agency_id'] ?? 0);
                $projectCustomer = trim((string)($_POST['project_customer'] ?? ''));
                $projectId = (int)($_POST['credential_project_id'] ?? 0);
                $photoAllowed = ($_POST['photo_allowed'] ?? '') === '1';
                $validFrom = mmCredentialDateOrNull((string)($_POST['valid_from'] ?? ''));
                $validUntil = mmCredentialDateOrNull((string)($_POST['valid_until'] ?? ''));
                $confidentiality = trim((string)($_POST['confidentiality_mode'] ?? 'public'));
                $publicNote = trim((string)($_POST['public_note'] ?? ''));

                if ($subjectUserId < 1 || $roleId < 1 || $agencyId < 1 || $projectId < 1 || $projectCustomer === '') {
                    throw new InvalidArgumentException('Person, Rolle, Agentur, Projektkunde und Projekt sind erforderlich.');
                }

                $controlled = mmCredentialResolveControlledSelection($subjectUserId, $roleId, $projectId);
                if ((int)$controlled['agency_id'] !== $agencyId || !hash_equals((string)$controlled['brand_name'], $projectCustomer)) {
                    throw new InvalidArgumentException('Agentur, Projektkunde und Projekt passen nicht zusammen.');
                }

                if (!in_array($confidentiality, ['public','confidential'], true)) {
                    throw new InvalidArgumentException('Ungültiger Vertraulichkeitsstatus.');
                }
                if ($validFrom !== null && $validUntil !== null && $validUntil < $validFrom) {
                    throw new InvalidArgumentException('Das Gültigkeitsende liegt vor dem Beginn.');
                }

                $stmt = mmDb()->prepare(
                    'UPDATE audit_verifications
                     SET public_title = :public_title,
                         public_partner = :public_partner,
                         public_client = :public_client,
                         valid_from = :valid_from,
                         valid_until = :valid_until,
                         confidentiality_mode = :confidentiality_mode,
                         public_note = :public_note,
                         person_name = :person_name,
                         role_label = :role_label,
                         credential_role_id = :credential_role_id,
                         agency_name = :agency_name,
                         agency_id = :agency_id,
                         project_name = :project_name,
                         credential_project_id = :credential_project_id,
                         brand_name = :brand_name,
                         scope_key = :scope_key,
                         photo_allowed = :photo_allowed,
                         updated_at = NOW()
                     WHERE id = :id
                       AND is_personal_verification = 1
                       AND is_active = 0'
                );
                $stmt->execute([
                    'public_title'=>$controlled['project_name'],
                    'public_partner'=>$controlled['agency_name'],
                    'public_client'=>$controlled['brand_name'],
                    'valid_from'=>$validFrom,
                    'valid_until'=>$validUntil,
                    'confidentiality_mode'=>$confidentiality,
                    'public_note'=>$publicNote !== '' ? $publicNote : null,
                    'person_name'=>$controlled['person_name'],
                    'role_label'=>$controlled['role_label'],
                    'credential_role_id'=>$controlled['credential_role_id'],
                    'agency_name'=>$controlled['agency_name'],
                    'agency_id'=>$controlled['agency_id'],
                    'project_name'=>$controlled['project_name'],
                    'credential_project_id'=>$controlled['credential_project_id'],
                    'brand_name'=>$controlled['brand_name'],
                    'scope_key'=>$controlled['scope_key'],
                    'photo_allowed'=>$photoAllowed ? 1 : 0,
                    'id'=>$id,
                ]);

                mmBackofficeAudit((int)$user['id'], 'verify_credential.updated', 'audit_verification', $id, [
                    'reference_code'=>(string)$credential['reference_code'],
                    'credential_role_id'=>$controlled['credential_role_id'],
                    'credential_project_id'=>$controlled['credential_project_id'],
                    'photo_allowed'=>$photoAllowed,
                ]);
                header('Location: /backoffice/credential.php?id=' . $id . '&saved=1', true, 303);
                exit;
            }

            if ($action === 'asset_upload') {
                $type = trim((string)($_POST['asset_type'] ?? ''));
                $columns = [
                    'photo'=>'photo_asset',
                    'brand_logo'=>'brand_logo_asset',
                    'agency_logo'=>'agency_logo_asset',
                    'document'=>'document_asset',
                ];
                if (!isset($columns[$type])) {
                    throw new InvalidArgumentException('Ungültiger Asset-Typ.');
                }

                $filename = mmCredentialStoreUploadedAsset($_FILES['asset_file'] ?? [], $id, $type);
                $column = $columns[$type];

                $sql = "UPDATE audit_verifications SET {$column} = :filename, updated_at = NOW()";
                $params = ['filename'=>$filename,'id'=>$id];

                if ($type === 'document') {
                    $label = trim((string)($_POST['document_label'] ?? 'Offizielles Legitimationsschreiben'));
                    if ($label === '' || mb_strlen($label) > 200) {
                        throw new InvalidArgumentException('Dokumentbezeichnung ist erforderlich.');
                    }
                    $sql .= ', document_label = :document_label, document_enabled = 1';
                    $params['document_label'] = $label;
                }

                $sql .= ' WHERE id = :id AND is_personal_verification = 1 AND is_active = 0';
                $stmt = mmDb()->prepare($sql);
                $stmt->execute($params);

                mmBackofficeAudit((int)$user['id'], 'verify_credential.asset_uploaded', 'audit_verification', $id, [
                    'asset_type'=>$type,
                    'filename'=>$filename
                ]);
                header('Location: /backoffice/credential.php?id=' . $id . '&asset_saved=1', true, 303);
                exit;
            }

            if ($action === 'asset_unbind') {
                $type = trim((string)($_POST['asset_type'] ?? ''));
                $columns = [
                    'photo'=>'photo_asset',
                    'brand_logo'=>'brand_logo_asset',
                    'agency_logo'=>'agency_logo_asset',
                    'document'=>'document_asset',
                ];
                if (!isset($columns[$type])) {
                    throw new InvalidArgumentException('Ungültiger Asset-Typ.');
                }

                $column = $columns[$type];
                $sql = "UPDATE audit_verifications SET {$column} = NULL, updated_at = NOW()";
                if ($type === 'document') {
                    $sql .= ', document_label = NULL, document_enabled = 0';
                }
                $sql .= ' WHERE id = :id AND is_personal_verification = 1 AND is_active = 0';

                $stmt = mmDb()->prepare($sql);
                $stmt->execute(['id'=>$id]);

                mmBackofficeAudit((int)$user['id'], 'verify_credential.asset_unbound', 'audit_verification', $id, [
                    'asset_type'=>$type
                ]);
                header('Location: /backoffice/credential.php?id=' . $id . '&asset_removed=1', true, 303);
                exit;
            }

            if ($action === 'activate') {
                $fresh = mmCredentialAdminFetch($id);
                if (!$fresh || (int)$fresh['is_active'] === 1) {
                    throw new RuntimeException('Der Ausweis ist nicht als inaktiver Draft verfügbar.');
                }

                $integrityErrors = mmCredentialIntegrityErrors($fresh);
                if ($integrityErrors !== []) {
                    throw new RuntimeException(
                        'Aktivierung blockiert: ' . implode('; ', $integrityErrors)
                    );
                }

                $pdo = mmDb();
                $pdo->beginTransaction();

                try {
                    $stmt = $pdo->prepare(
                        'UPDATE audit_verifications
                         SET is_active = 1, updated_at = NOW()
                         WHERE id = :id
                           AND is_personal_verification = 1
                           AND is_active = 0'
                    );
                    $stmt->execute(['id'=>$id]);

                    if ($stmt->rowCount() !== 1) {
                        throw new RuntimeException('Aktivierung konnte nicht eindeutig gespeichert werden.');
                    }

                    $supersedesId = (int)($fresh['supersedes_verification_id'] ?? 0);
                    if ($supersedesId > 0) {
                        $deactivatePrevious = $pdo->prepare(
                            'UPDATE audit_verifications
                             SET is_active = 0, updated_at = NOW()
                             WHERE id = :id
                               AND is_personal_verification = 1
                               AND is_active = 1'
                        );
                        $deactivatePrevious->execute(['id'=>$supersedesId]);
                    }

                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }

                mmBackofficeAudit((int)$user['id'], 'verify_credential.activated', 'audit_verification', $id, [
                    'reference_code'=>(string)$fresh['reference_code'],
                    'supersedes_verification_id'=>(int)($fresh['supersedes_verification_id'] ?? 0) ?: null
                ]);

                header('Location: /backoffice/credential.php?id=' . $id . '&activated=1', true, 303);
                exit;
            }

            if ($action === 'deactivate') {
                if ((int)$credential['is_active'] !== 1) {
                    throw new RuntimeException('Nur aktive Verify-Ausweise können deaktiviert werden.');
                }

                $stmt = mmDb()->prepare(
                    'UPDATE audit_verifications
                     SET is_active = 0, updated_at = NOW()
                     WHERE id = :id
                       AND is_personal_verification = 1
                       AND is_active = 1'
                );
                $stmt->execute(['id'=>$id]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('Deaktivierung konnte nicht eindeutig gespeichert werden.');
                }

                mmBackofficeAudit((int)$user['id'], 'verify_credential.deactivated', 'audit_verification', $id, [
                    'reference_code'=>(string)$credential['reference_code']
                ]);

                header('Location: /backoffice/credential.php?id=' . $id . '&deactivated=1', true, 303);
                exit;
            }

            if ($action === 'create_revision') {
                if ((int)$credential['is_active'] !== 1) {
                    throw new RuntimeException('Eine Revision kann nur aus einem aktiven Verify-Ausweis erzeugt werden.');
                }

                $newReference = mmCredentialGenerateVerifyReference();
                $nextRevision = max(2, ((int)($credential['revision_no'] ?? 1)) + 1);

                $stmt = mmDb()->prepare(
                    "INSERT INTO audit_verifications
                     (reference_code, public_title, public_partner, public_client,
                      valid_from, valid_until, confidentiality_mode, public_note,
                      person_name, role_label, credential_role_id, agency_name, agency_id,
                      project_name, credential_project_id, brand_name,
                      photo_asset, brand_logo_asset, agency_logo_asset, scope_key,
                      document_asset, document_label, document_enabled, print_card_enabled, photo_allowed,
                      subject_user_id, is_personal_verification, is_active, supersedes_verification_id, revision_no,
                      created_at, updated_at)
                     VALUES
                     (:reference_code, :public_title, :public_partner, :public_client,
                      :valid_from, :valid_until, :confidentiality_mode, :public_note,
                      :person_name, :role_label, :credential_role_id, :agency_name, :agency_id,
                      :project_name, :credential_project_id, :brand_name,
                      :photo_asset, :brand_logo_asset, :agency_logo_asset, :scope_key,
                      :document_asset, :document_label, :document_enabled, :print_card_enabled, :photo_allowed,
                      :subject_user_id, 1, 0, :supersedes_verification_id, :revision_no,
                      NOW(), NOW())"
                );
                $stmt->execute([
                    'reference_code'=>$newReference,
                    'public_title'=>$credential['public_title'],
                    'public_partner'=>$credential['public_partner'],
                    'public_client'=>$credential['public_client'],
                    'valid_from'=>$credential['valid_from'],
                    'valid_until'=>$credential['valid_until'],
                    'confidentiality_mode'=>$credential['confidentiality_mode'],
                    'public_note'=>$credential['public_note'],
                    'person_name'=>$credential['person_name'],
                    'role_label'=>$credential['role_label'],
                    'credential_role_id'=>$credential['credential_role_id'] ?? null,
                    'agency_name'=>$credential['agency_name'],
                    'agency_id'=>$credential['agency_id'] ?? null,
                    'project_name'=>$credential['project_name'],
                    'credential_project_id'=>$credential['credential_project_id'] ?? null,
                    'brand_name'=>$credential['brand_name'],
                    'photo_asset'=>$credential['photo_asset'],
                    'brand_logo_asset'=>$credential['brand_logo_asset'],
                    'agency_logo_asset'=>$credential['agency_logo_asset'],
                    'scope_key'=>$credential['scope_key'],
                    'document_asset'=>$credential['document_asset'],
                    'document_label'=>$credential['document_label'],
                    'document_enabled'=>$credential['document_enabled'],
                    'print_card_enabled'=>$credential['print_card_enabled'],
                    'photo_allowed'=>$credential['photo_allowed'] ?? 0,
                    'subject_user_id'=>$credential['subject_user_id'] ?? null,
                    'supersedes_verification_id'=>$id,
                    'revision_no'=>$nextRevision,
                ]);

                $revisionId = (int)mmDb()->lastInsertId();

                mmBackofficeAudit((int)$user['id'], 'verify_credential.revision_created', 'audit_verification', $revisionId, [
                    'source_id'=>$id,
                    'source_reference'=>(string)$credential['reference_code'],
                    'new_reference'=>$newReference,
                    'revision_no'=>$nextRevision
                ]);

                header('Location: /backoffice/credential.php?id=' . $revisionId . '&revision_created=1', true, 303);
                exit;
            }

            throw new InvalidArgumentException('Unbekannte Aktion.');
        } catch (InvalidArgumentException|RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Die Ausweisänderung konnte nicht gespeichert werden.';
        }
    }
}

$credential = mmCredentialAdminFetch($id);
$state = mmCredentialVerifyState($credential);
$integrityErrors = mmCredentialIntegrityErrors($credential);
$activationReady = (int)$credential['is_active'] !== 1 && $integrityErrors === [];

$assets = [
    'Foto'=>$credential['photo_asset'] ?? null,
    'Markenlogo'=>$credential['brand_logo_asset'] ?? null,
    'Agenturlogo'=>$credential['agency_logo_asset'] ?? null,
    'Dokument'=>$credential['document_asset'] ?? null,
];

mmHeader('Verify-Ausweis', 'Projektbezogenen Verify-Ausweis verwalten.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Ausweis-Service · <?= mmEscape((string)$credential['reference_code']) ?></p>
    <h1><?= mmEscape((string)($credential['project_name'] ?: 'Verify-Ausweis')) ?></h1>
    <p class="lead"><?= mmEscape(mmCredentialProjectLabel($credential)) ?></p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/credentials.php">Zurück zu Ausweisen</a>
      <a class="button secondary" href="/verify?code=<?= rawurlencode((string)$credential['reference_code']) ?>">Verify öffnen</a>
    </div>
  </div>
</section>

<section class="section">
  <?php if (isset($_GET['created'])): ?><div class="alert success"><strong>Ausweis-Draft angelegt.</strong> Die Verify-Referenz wurde automatisch erzeugt.</div><?php endif; ?>
  <?php if (isset($_GET['saved'])): ?><div class="alert success"><strong>Ausweis gespeichert.</strong></div><?php endif; ?>
  <?php if (isset($_GET['subject_saved'])): ?><div class="alert success"><strong>Private Ausweis-Zuordnung gespeichert.</strong></div><?php endif; ?>
  <?php if (isset($_GET['asset_saved'])): ?><div class="alert success"><strong>Asset sicher gespeichert und gebunden.</strong></div><?php endif; ?>
  <?php if (isset($_GET['asset_removed'])): ?><div class="alert success"><strong>Asset-Bindung entfernt.</strong></div><?php endif; ?>
  <?php if (isset($_GET['activated'])): ?><div class="alert success"><strong>Verify-Ausweis aktiviert.</strong> Der Datensatz ist jetzt produktiv und über Verify gültig, sofern er im Gültigkeitszeitraum liegt.</div><?php endif; ?>
  <?php if (isset($_GET['deactivated'])): ?><div class="alert success"><strong>Verify-Ausweis deaktiviert.</strong> Die Referenz ist nicht mehr produktiv gültig.</div><?php endif; ?>
  <?php if (isset($_GET['revision_created'])): ?><div class="alert success"><strong>Revision als neuer Draft angelegt.</strong> Der bisherige Ausweis wurde nicht verändert.</div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="grid two">
    <article class="card">
      <span class="badge">Verify Credential</span>
      <h2><?= mmEscape((string)$credential['reference_code']) ?></h2>
      <p><?= mmBackofficeStatusBadge($state) ?></p>
      <p><strong><?= mmEscape((string)$credential['person_name']) ?></strong><br><?= mmEscape((string)$credential['role_label']) ?></p>
      <p><?= mmEscape((string)$credential['agency_name']) ?> · <?= mmEscape((string)$credential['brand_name']) ?></p>
      <p><strong>Gültig:</strong> <?= mmEscape((string)($credential['valid_from'] ?: '—')) ?> – <?= mmEscape((string)($credential['valid_until'] ?: '—')) ?></p>
      <p><strong>Fotoerlaubnis:</strong> <?= mmBackofficeStatusBadge((int)($credential['photo_allowed'] ?? 0) === 1 ? 'active' : 'inactive', (int)($credential['photo_allowed'] ?? 0) === 1 ? 'Fotografieren erlaubt' : 'nicht erlaubt') ?></p>
      <p><strong>Revision:</strong> <?= (int)($credential['revision_no'] ?? 1) ?><?php if (!empty($credential['supersedes_verification_id'])): ?> · Folgeversion<?php endif; ?></p>
    </article>

    <article class="card">
      <span class="badge">Ausstattung</span>
      <h3>Credential-Vollständigkeit</h3>
      <div class="credential-asset-state">
        <?php foreach ($assets as $label=>$value): ?>
          <div><strong><?= mmEscape($label) ?></strong><?= mmBackofficeStatusBadge($value ? 'active' : 'pending', $value ? 'vorhanden' : 'fehlt') ?></div>
        <?php endforeach; ?>
        <div><strong>Scope</strong><?= mmBackofficeStatusBadge(!empty($credential['scope_key']) ? 'active' : 'pending', !empty($credential['scope_key']) ? (string)$credential['scope_key'] : 'fehlt') ?></div>
        <div><strong>Dokument aktiv</strong><?= mmBackofficeStatusBadge((int)$credential['document_enabled'] === 1 ? 'active' : 'pending', (int)$credential['document_enabled'] === 1 ? 'ja' : 'nein') ?></div>
      </div>
      <p class="partner-note">Assets liegen weiterhin geschützt außerhalb des Webroots. Die Upload-/Bindungsoberfläche kommt als nächster Schritt auf Basis dieser bestehenden Sicherheitslogik.</p>
    </article>
  </div>
</section>

<section class="section">
  <div class="form-card credential-editor">
    <div class="section-head">
      <p class="eyebrow">Geschützte Ausstattung</p>
      <h2>Assets & Scope.</h2>
      <p>Dateien werden ausschließlich in den privaten Verify-Speicher geschrieben. Aktive Ausweise bleiben schreibgeschützt.</p>
    </div>

    <div class="credential-asset-manager">
      <?php
      $assetConfig = [
          'photo'=>['Foto','photo_asset','Bild bis 5 MB'],
          'brand_logo'=>['Markenlogo','brand_logo_asset','PNG/JPG/WebP bis 5 MB'],
          'agency_logo'=>['Agenturlogo','agency_logo_asset','PNG/JPG/WebP bis 5 MB'],
          'document'=>['Dokument','document_asset','PDF bis 10 MB'],
      ];
      foreach ($assetConfig as $assetType=>[$label,$column,$hint]):
          $bound = trim((string)($credential[$column] ?? ''));
      ?>
        <article class="credential-asset-item">
          <div>
            <strong><?= mmEscape($label) ?></strong>
            <small><?= mmEscape($bound !== '' ? $bound : $hint) ?></small>
          </div>

          <?php if ($bound !== ''): ?>
            <div class="credential-asset-actions">
              <a class="button secondary" target="_blank" rel="noopener" href="/backoffice/credential-asset.php?id=<?= $id ?>&type=<?= rawurlencode($assetType) ?>">Anzeigen</a>
              <?php if ((int)$credential['is_active'] !== 1): ?>
                <form method="post" action="/backoffice/credential.php?id=<?= $id ?>">
                  <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <input type="hidden" name="action" value="asset_unbind">
                  <input type="hidden" name="asset_type" value="<?= mmEscape($assetType) ?>">
                  <button type="submit" class="button secondary">Bindung lösen</button>
                </form>
              <?php endif; ?>
            </div>
          <?php elseif ((int)$credential['is_active'] !== 1): ?>
            <form method="post" action="/backoffice/credential.php?id=<?= $id ?>" enctype="multipart/form-data" class="credential-asset-upload">
              <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
              <input type="hidden" name="id" value="<?= $id ?>">
              <input type="hidden" name="action" value="asset_upload">
              <input type="hidden" name="asset_type" value="<?= mmEscape($assetType) ?>">
              <input type="file" name="asset_file" required accept="<?= $assetType === 'document' ? 'application/pdf' : 'image/png,image/jpeg,image/webp' ?>">
              <?php if ($assetType === 'document'): ?>
                <input name="document_label" maxlength="200" value="<?= mmEscape((string)($credential['document_label'] ?: 'Offizielles Legitimationsschreiben')) ?>" placeholder="Dokumentbezeichnung">
              <?php endif; ?>
              <button type="submit">Hochladen</button>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <div class="credential-editor-section credential-scope-editor">
      <div class="credential-editor-head"><span>04</span><div><strong>Projekt-Scope</strong><small>Wird aus den kontrollierten Projekt-Stammdaten übernommen</small></div></div>
      <div class="credential-scope-readonly">
        <strong><?= mmEscape((string)($credential['scope_key'] ?: 'Kein Scope hinterlegt')) ?></strong>
        <small>Änderungen erfolgen über die Ausweis-Projektstammdaten, nicht am einzelnen Ausweis.</small>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="form-card credential-editor">
    <div class="section-head">
      <p class="eyebrow">Privater Zugriff</p>
      <h2>Ausweis-Person.</h2>
      <p>Diese Auswahl ist die verbindliche Identität des Ausweises. Der aufgedruckte Name wird automatisch aus dem Elite-Mitglied übernommen und ist nicht frei editierbar.</p>
    </div>
    <form method="post" action="/backoffice/credential.php?id=<?= $id ?>">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <input type="hidden" name="action" value="set_subject">
      <label>Private Ausweis-Person
        <select name="subject_user_id"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>>
          <option value="">Bitte aktive Elite-Person auswählen</option>
          <?php foreach ($credentialSubjects as $subject): ?>
            <?php
              $subjectLabel = (string)$subject['display_name'];
              $subjectMeta = (string)$subject['member_code'] . ' · ' . (string)$subject['email'];
            ?>
            <option value="<?= (int)$subject['id'] ?>"<?= (int)($credential['subject_user_id'] ?? 0) === (int)$subject['id'] ? ' selected' : '' ?>>
              <?= mmEscape($subjectLabel . ' — ' . $subjectMeta) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php if ((int)$credential['is_active'] !== 1): ?>
        <div class="elite-profile-actions"><button type="submit">Ausweis-Person speichern</button></div>
      <?php endif; ?>
    </form>
  </div>
</section>

<section class="section">
  <div class="form-card credential-activation-card">
    <div class="section-head">
      <p class="eyebrow">Freigabe</p>
      <h2>Verify-Aktivierung.</h2>
      <?php if ((int)$credential['is_active'] === 1): ?>
        <p>Dieser Ausweis ist aktiv und produktiv. Änderungen erfolgen über eine neue Revision oder durch kontrollierte Deaktivierung.</p>
      <?php elseif ($activationReady): ?>
        <p>Alle Integritätsbedingungen sind erfüllt. Der Ausweis kann jetzt produktiv aktiviert werden.</p>
      <?php else: ?>
        <p>Die Aktivierung bleibt gesperrt, bis alle Pflichtbestandteile vollständig und technisch gültig sind.</p>
      <?php endif; ?>
    </div>

    <?php if ((int)$credential['is_active'] === 1): ?>
      <div class="credential-lifecycle-actions">
        <form method="post" action="/backoffice/credential.php?id=<?= $id ?>" onsubmit="return confirm('Neue Revision aus <?= mmEscape((string)$credential['reference_code']) ?> als inaktiven Draft anlegen?');">
          <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="create_revision">
          <button type="submit">Revision als Draft anlegen</button>
        </form>
        <form method="post" action="/backoffice/credential.php?id=<?= $id ?>" onsubmit="return confirm('Verify-Ausweis <?= mmEscape((string)$credential['reference_code']) ?> wirklich deaktivieren?');">
          <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="deactivate">
          <button type="submit" class="button secondary">Ausweis deaktivieren</button>
        </form>
      </div>
    <?php else: ?>
      <?php if ($integrityErrors !== []): ?>
        <div class="credential-integrity-block">
          <strong>Noch offen:</strong>
          <ul>
            <?php foreach ($integrityErrors as $integrityError): ?>
              <li><?= mmEscape($integrityError) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <div class="alert success"><strong>Integritätsprüfung vollständig.</strong> Foto, Logos, Scope, Dokument, Laufzeit und private Dateien sind gültig.</div>
        <form method="post" action="/backoffice/credential.php?id=<?= $id ?>" class="credential-activate-form" onsubmit="return confirm('Verify-Ausweis <?= mmEscape((string)$credential['reference_code']) ?> wirklich produktiv aktivieren?');">
          <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="activate">
          <button type="submit">Ausweis produktiv aktivieren</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="form-card credential-editor">
    <div class="section-head">
      <p class="eyebrow"><?= (int)$credential['is_active'] === 1 ? 'Aktiver Ausweis' : 'Draft bearbeiten' ?></p>
      <h2>Ausweisdaten.</h2>
      <?php if ((int)$credential['is_active'] === 1): ?>
        <p>Aktive Ausweise sind hier derzeit schreibgeschützt, damit produktive Verify-Credentials nicht versehentlich verändert werden.</p>
      <?php endif; ?>
    </div>

    <form method="post" action="/backoffice/credential.php?id=<?= $id ?>">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <input type="hidden" name="action" value="save_details">

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>01</span><div><strong>Person & Rolle</strong></div></div>
        <div class="form-grid">
          <label>Person
            <input value="<?= mmEscape((string)$credential['person_name']) ?>" disabled>
            <small class="field-hint">Kommt verbindlich aus der zugeordneten Elite-Person.</small>
          </label>
          <label>Rolle
            <select name="credential_role_id" required<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>>
              <option value="">Bitte Rolle auswählen</option>
              <?php foreach ($credentialRoles as $roleOption): ?>
                <option value="<?= (int)$roleOption['id'] ?>"<?= (int)($credential['credential_role_id'] ?? 0) === (int)$roleOption['id'] ? ' selected' : '' ?>><?= mmEscape((string)$roleOption['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </div>

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>02</span><div><strong>Projekt</strong></div></div>
        <div class="form-grid">
          <label>Agentur
            <select name="agency_id" required data-credential-agency<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>>
              <option value="">Bitte auswählen</option>
              <?php foreach ($agencyOptions as $agencyId=>$agencyName): ?>
                <option value="<?= (int)$agencyId ?>"<?= (int)($credential['agency_id'] ?? 0) === (int)$agencyId ? ' selected' : '' ?>><?= mmEscape($agencyName) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>Projektkunde
            <select name="project_customer" required data-credential-customer<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>>
              <option value="">Bitte auswählen</option>
              <?php foreach ($customerOptions as $customerOption): ?>
                <option value="<?= mmEscape((string)$customerOption['customer_name']) ?>"
                        data-agency-id="<?= (int)$customerOption['agency_id'] ?>"
                        <?= (string)$credential['brand_name'] === (string)$customerOption['customer_name'] && (int)($credential['agency_id'] ?? 0) === (int)$customerOption['agency_id'] ? 'selected' : '' ?>>
                  <?= mmEscape((string)$customerOption['customer_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="wide">Projekt
            <select name="credential_project_id" required data-credential-project<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>>
              <option value="">Bitte Projekt auswählen</option>
              <?php foreach ($credentialProjects as $projectOption): ?>
                <option value="<?= (int)$projectOption['id'] ?>"
                        data-agency-id="<?= (int)$projectOption['agency_id'] ?>"
                        data-customer="<?= mmEscape((string)$projectOption['customer_name']) ?>"
                        <?= (int)($credential['credential_project_id'] ?? 0) === (int)$projectOption['id'] ? 'selected' : '' ?>>
                  <?= mmEscape((string)$projectOption['project_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </div>

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>03</span><div><strong>Gültigkeit & Sichtbarkeit</strong></div></div>
        <div class="form-grid">
          <label>Gültig ab<input type="date" name="valid_from" value="<?= mmEscape((string)$credential['valid_from']) ?>"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>></label>
          <label>Gültig bis<input type="date" name="valid_until" value="<?= mmEscape((string)$credential['valid_until']) ?>"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>></label>
          <label>Vertraulichkeit
            <select name="confidentiality_mode"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>>
              <option value="public"<?= $credential['confidentiality_mode'] === 'public' ? ' selected' : '' ?>>Öffentlich im Verify-Kontext</option>
              <option value="confidential"<?= $credential['confidentiality_mode'] === 'confidential' ? ' selected' : '' ?>>Vertraulich</option>
            </select>
          </label>
          <label class="wide credential-photo-permission"><input type="checkbox" name="photo_allowed" value="1"<?= (int)($credential['photo_allowed'] ?? 0) === 1 ? ' checked' : '' ?><?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>> <span><strong>Fotografieren erlaubt</strong><small class="field-hint">Nur aktivieren, wenn das Projekt bzw. Legitimationsschreiben Fotoaufnahmen ausdrücklich erlaubt.</small></span></label>
          <label class="wide">Öffentlicher Hinweis<textarea name="public_note"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>><?= mmEscape((string)($credential['public_note'] ?? '')) ?></textarea></label>
        </div>
      </div>

      <?php if ((int)$credential['is_active'] !== 1): ?>
        <div class="elite-profile-actions"><button type="submit">Draft speichern</button></div>
      <?php endif; ?>
    </form>
  </div>
</section>
<script>
(() => {
  const agency = document.querySelector('[data-credential-agency]');
  const customer = document.querySelector('[data-credential-customer]');
  const project = document.querySelector('[data-credential-project]');
  if (!agency || !customer || !project) return;

  const apply = () => {
    const agencyId = agency.value;
    [...customer.options].forEach((option, index) => {
      if (index === 0) return;
      option.hidden = option.dataset.agencyId !== agencyId;
      if (option.hidden && option.selected) customer.value = '';
    });

    const customerName = customer.value;
    [...project.options].forEach((option, index) => {
      if (index === 0) return;
      option.hidden = option.dataset.agencyId !== agencyId || option.dataset.customer !== customerName;
      if (option.hidden && option.selected) project.value = '';
    });
  };

  agency.addEventListener('change', () => { customer.value = ''; project.value = ''; apply(); });
  customer.addEventListener('change', () => { project.value = ''; apply(); });
  apply();
})();
</script>
<?php mmFooter(); ?>
