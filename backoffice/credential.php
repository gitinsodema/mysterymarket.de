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
        $error = 'Aktive Verify-Ausweise sind in dieser ersten Editor-Stufe schreibgeschützt. Änderungen erfolgen erst mit dem kontrollierten Aktivierungs-/Revisionsworkflow.';
    } else {
        try {
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
            foreach (['agency'=>$agencyName,'project'=>$projectName,'brand'=>$brandName] as $field=>$value) {
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

            mmBackofficeAudit(
                (int)$user['id'],
                'verify_credential.updated',
                'audit_verification',
                $id,
                ['reference_code'=>(string)$credential['reference_code']]
            );

            header('Location: /backoffice/credential.php?id=' . $id . '&saved=1', true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Der Ausweis konnte nicht gespeichert werden.';
        }
    }
}

$credential = mmCredentialAdminFetch($id);
$state = mmCredentialVerifyState($credential);

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
      <p class="eyebrow"><?= (int)$credential['is_active'] === 1 ? 'Aktiver Ausweis' : 'Draft bearbeiten' ?></p>
      <h2>Ausweisdaten.</h2>
      <?php if ((int)$credential['is_active'] === 1): ?>
        <p>Aktive Ausweise sind hier derzeit schreibgeschützt, damit produktive Verify-Credentials nicht versehentlich verändert werden.</p>
      <?php endif; ?>
    </div>

    <form method="post" action="/backoffice/credential.php?id=<?= $id ?>">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= $id ?>">

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
