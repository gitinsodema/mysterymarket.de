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

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } elseif ((int)$credential['is_active'] === 1) {
        $error = 'Aktive Verify-Ausweise sind schreibgeschützt. Änderungen erfolgen erst über einen kontrollierten Revisionsworkflow.';
    } else {
        $action = (string)($_POST['action'] ?? 'save_details');

        try {
            if ($action === 'save_details') {
                $personName = trim((string)($_POST['person_name'] ?? ''));
                $roleLabel = trim((string)($_POST['role_label'] ?? ''));
                $agencyName = trim((string)($_POST['agency_name'] ?? ''));
                $projectName = trim((string)($_POST['project_name'] ?? ''));
                $brandName = trim((string)($_POST['brand_name'] ?? ''));
                $validFrom = mmCredentialDateOrNull((string)($_POST['valid_from'] ?? ''));
                $validUntil = mmCredentialDateOrNull((string)($_POST['valid_until'] ?? ''));
                $confidentiality = trim((string)($_POST['confidentiality_mode'] ?? 'public'));
                $publicNote = trim((string)($_POST['public_note'] ?? ''));

                if ($personName === '' || mb_strlen($personName) > 150) {
                    throw new InvalidArgumentException('Person ist erforderlich.');
                }
                if ($roleLabel === '' || mb_strlen($roleLabel) > 120) {
                    throw new InvalidArgumentException('Rolle ist erforderlich.');
                }
                foreach ([$agencyName,$projectName,$brandName] as $value) {
                    if ($value === '' || mb_strlen($value) > 200) {
                        throw new InvalidArgumentException('Agentur, Projekt und Marke/Kunde sind erforderlich.');
                    }
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
                         agency_name = :agency_name,
                         project_name = :project_name,
                         brand_name = :brand_name,
                         updated_at = NOW()
                     WHERE id = :id
                       AND is_personal_verification = 1
                       AND is_active = 0'
                );
                $stmt->execute([
                    'public_title'=>$projectName,
                    'public_partner'=>$agencyName,
                    'public_client'=>$brandName,
                    'valid_from'=>$validFrom,
                    'valid_until'=>$validUntil,
                    'confidentiality_mode'=>$confidentiality,
                    'public_note'=>$publicNote !== '' ? $publicNote : null,
                    'person_name'=>$personName,
                    'role_label'=>$roleLabel,
                    'agency_name'=>$agencyName,
                    'project_name'=>$projectName,
                    'brand_name'=>$brandName,
                    'id'=>$id,
                ]);

                mmBackofficeAudit((int)$user['id'], 'verify_credential.updated', 'audit_verification', $id, [
                    'reference_code'=>(string)$credential['reference_code']
                ]);
                header('Location: /backoffice/credential.php?id=' . $id . '&saved=1', true, 303);
                exit;
            }

            if ($action === 'scope_update') {
                $scopeKey = trim((string)($_POST['scope_key'] ?? ''));
                $allowedScopes = ['', 'vodafone_skopos_2026', 'hp_bare_retail_2025_2026'];
                if (!in_array($scopeKey, $allowedScopes, true)) {
                    throw new InvalidArgumentException('Unbekannter Scope.');
                }

                $stmt = mmDb()->prepare(
                    'UPDATE audit_verifications
                     SET scope_key = :scope_key, updated_at = NOW()
                     WHERE id = :id AND is_personal_verification = 1 AND is_active = 0'
                );
                $stmt->execute([
                    'scope_key'=>$scopeKey !== '' ? $scopeKey : null,
                    'id'=>$id,
                ]);
                mmBackofficeAudit((int)$user['id'], 'verify_credential.scope_updated', 'audit_verification', $id, [
                    'scope_key'=>$scopeKey !== '' ? $scopeKey : null
                ]);
                header('Location: /backoffice/credential.php?id=' . $id . '&scope_saved=1', true, 303);
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

                $stmt = mmDb()->prepare(
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

                mmBackofficeAudit((int)$user['id'], 'verify_credential.activated', 'audit_verification', $id, [
                    'reference_code'=>(string)$fresh['reference_code']
                ]);

                header('Location: /backoffice/credential.php?id=' . $id . '&activated=1', true, 303);
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
  <?php if (isset($_GET['scope_saved'])): ?><div class="alert success"><strong>Scope gespeichert.</strong></div><?php endif; ?>
  <?php if (isset($_GET['asset_saved'])): ?><div class="alert success"><strong>Asset sicher gespeichert und gebunden.</strong></div><?php endif; ?>
  <?php if (isset($_GET['asset_removed'])): ?><div class="alert success"><strong>Asset-Bindung entfernt.</strong></div><?php endif; ?>
  <?php if (isset($_GET['activated'])): ?><div class="alert success"><strong>Verify-Ausweis aktiviert.</strong> Der Datensatz ist jetzt produktiv und über Verify gültig, sofern er im Gültigkeitszeitraum liegt.</div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="grid two">
    <article class="card">
      <span class="badge">Verify Credential</span>
      <h2><?= mmEscape((string)$credential['reference_code']) ?></h2>
      <p><?= mmBackofficeStatusBadge($state) ?></p>
      <p><strong><?= mmEscape((string)$credential['person_name']) ?></strong><br><?= mmEscape((string)$credential['role_label']) ?></p>
      <p><?= mmEscape((string)$credential['agency_name']) ?> · <?= mmEscape((string)$credential['brand_name']) ?></p>
      <p><strong>Gültig:</strong> <?= mmEscape((string)($credential['valid_from'] ?: '—')) ?> – <?= mmEscape((string)($credential['valid_until'] ?: '—')) ?></p>
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
      <div class="credential-editor-head"><span>04</span><div><strong>Projekt-Scope</strong><small>Nur bereits definierte Verify-Regeln auswählen</small></div></div>
      <form method="post" action="/backoffice/credential.php?id=<?= $id ?>" class="credential-scope-form">
        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="scope_update">
        <select name="scope_key"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>>
          <option value=""<?= empty($credential['scope_key']) ? ' selected' : '' ?>>Kein Scope / noch nicht festgelegt</option>
          <option value="vodafone_skopos_2026"<?= $credential['scope_key'] === 'vodafone_skopos_2026' ? ' selected' : '' ?>>Vodafone / SKOPOS NEXT 2026</option>
          <option value="hp_bare_retail_2025_2026"<?= $credential['scope_key'] === 'hp_bare_retail_2025_2026' ? ' selected' : '' ?>>HP / BARE Retail 2025/2026</option>
        </select>
        <?php if ((int)$credential['is_active'] !== 1): ?><button type="submit">Scope speichern</button><?php endif; ?>
      </form>
    </div>
  </div>
</section>

<section class="section">
  <div class="form-card credential-activation-card">
    <div class="section-head">
      <p class="eyebrow">Freigabe</p>
      <h2>Verify-Aktivierung.</h2>
      <?php if ((int)$credential['is_active'] === 1): ?>
        <p>Dieser Ausweis ist aktiv und produktiv.</p>
      <?php elseif ($activationReady): ?>
        <p>Alle Integritätsbedingungen sind erfüllt. Der Ausweis kann jetzt produktiv aktiviert werden.</p>
      <?php else: ?>
        <p>Die Aktivierung bleibt gesperrt, bis alle Pflichtbestandteile vollständig und technisch gültig sind.</p>
      <?php endif; ?>
    </div>

    <?php if ((int)$credential['is_active'] !== 1): ?>
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
          <label>Person<input name="person_name" maxlength="150" required value="<?= mmEscape((string)$credential['person_name']) ?>"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>></label>
          <label>Rolle<input name="role_label" maxlength="120" required value="<?= mmEscape((string)$credential['role_label']) ?>"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>></label>
        </div>
      </div>

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>02</span><div><strong>Projekt</strong></div></div>
        <div class="form-grid">
          <label>Agentur<input name="agency_name" maxlength="200" required value="<?= mmEscape((string)$credential['agency_name']) ?>"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>></label>
          <label>Marke / Kunde<input name="brand_name" maxlength="200" required value="<?= mmEscape((string)$credential['brand_name']) ?>"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>></label>
          <label class="wide">Projekt<input name="project_name" maxlength="200" required value="<?= mmEscape((string)$credential['project_name']) ?>"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>></label>
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
          <label class="wide">Öffentlicher Hinweis<textarea name="public_note"<?= (int)$credential['is_active'] === 1 ? ' disabled' : '' ?>><?= mmEscape((string)($credential['public_note'] ?? '')) ?></textarea></label>
        </div>
      </div>

      <?php if ((int)$credential['is_active'] !== 1): ?>
        <div class="elite-profile-actions"><button type="submit">Draft speichern</button></div>
      <?php endif; ?>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
