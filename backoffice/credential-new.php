<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

$credentialSubjects = mmCredentialControlledSubjects();
$credentialRoles = mmCredentialControlledRoles();
$credentialProjects = mmCredentialControlledProjects();

$agencyOptions = [];
foreach ($credentialProjects as $projectOption) {
    $agencyOptions[(int)$projectOption['agency_id']] = (string)$projectOption['agency_name'];
}

$values = [
    'subject_user_id'=>'',
    'credential_role_id'=>'',
    'agency_id'=>'',
    'credential_project_id'=>'',
    'valid_from'=>'',
    'valid_until'=>'',
    'confidentiality_mode'=>'public',
    'public_note'=>'Persönliche Audit-Legitimation.',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (array_keys($values) as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        try {
            $validFrom = mmCredentialDateOrNull($values['valid_from']);
            $validUntil = mmCredentialDateOrNull($values['valid_until']);
            $subjectUserId = (int)$values['subject_user_id'];
            $roleId = (int)$values['credential_role_id'];
            $projectId = (int)$values['credential_project_id'];
            $agencyId = (int)$values['agency_id'];
            if ($subjectUserId < 1 || $roleId < 1 || $projectId < 1 || $agencyId < 1) {
                throw new InvalidArgumentException('Person, Rolle, Agentur und Projekt sind erforderlich.');
            }

            $controlled = mmCredentialResolveControlledSelection($subjectUserId, $roleId, $projectId);
            if ((int)$controlled['agency_id'] !== $agencyId) {
                throw new InvalidArgumentException('Agentur und Projekt passen nicht zusammen.');
            }
            if (!in_array($values['confidentiality_mode'], ['public','confidential'], true)) {
                throw new InvalidArgumentException('Ungültiger Vertraulichkeitsstatus.');
            }
            if ($validFrom !== null && $validUntil !== null && $validUntil < $validFrom) {
                throw new InvalidArgumentException('Das Gültigkeitsende liegt vor dem Beginn.');
            }

            $reference = mmCredentialGenerateVerifyReference();
            $stmt = mmDb()->prepare(
                "INSERT INTO audit_verifications
                 (reference_code, public_title, public_partner, public_client,
                  valid_from, valid_until, confidentiality_mode, public_note,
                  person_name, role_label, credential_role_id, agency_name, agency_id,
                  project_name, credential_project_id, brand_name,
                  photo_asset, brand_logo_asset, agency_logo_asset, scope_key,
                  document_asset, document_label, document_enabled,
                  print_card_enabled, photo_allowed, subject_user_id, is_personal_verification, is_active,
                  created_at, updated_at)
                 VALUES
                 (:reference_code, :public_title, :public_partner, :public_client,
                  :valid_from, :valid_until, :confidentiality_mode, :public_note,
                  :person_name, :role_label, :credential_role_id, :agency_name, :agency_id,
                  :project_name, :credential_project_id, :brand_name,
                  :photo_asset, :brand_logo_asset, NULL, :scope_key,
                  :document_asset, :document_label, :document_enabled,
                  1, :photo_allowed, :subject_user_id, 1, 0,
                  NOW(), NOW())"
            );
            $stmt->execute([
                'subject_user_id'=>$subjectUserId,
                'reference_code'=>$reference,
                'public_title'=>$controlled['project_name'],
                'public_partner'=>$controlled['agency_name'],
                'public_client'=>$controlled['brand_name'],
                'valid_from'=>$validFrom,
                'valid_until'=>$validUntil,
                'confidentiality_mode'=>$values['confidentiality_mode'],
                'public_note'=>$values['public_note'] !== '' ? $values['public_note'] : null,
                'person_name'=>$controlled['person_name'],
                'role_label'=>$controlled['role_label'],
                'credential_role_id'=>$controlled['credential_role_id'],
                'agency_name'=>$controlled['agency_name'],
                'agency_id'=>$controlled['agency_id'],
                'project_name'=>$controlled['project_name'],
                'credential_project_id'=>$controlled['credential_project_id'],
                'brand_name'=>$controlled['brand_name'],
                'photo_asset'=>$controlled['photo_asset'],
                'brand_logo_asset'=>$controlled['brand_logo_asset'],
                'scope_key'=>$controlled['scope_key'],
                'document_asset'=>$controlled['document_asset'],
                'document_label'=>$controlled['document_label'],
                'document_enabled'=>$controlled['document_asset'] ? 1 : 0,
                'photo_allowed'=>$controlled['photo_allowed'],
            ]);

            $id = (int)mmDb()->lastInsertId();
            mmBackofficeAudit(
                (int)$user['id'],
                'verify_credential.created',
                'audit_verification',
                $id,
                ['reference_code'=>$reference]
            );

            header('Location: /backoffice/credential.php?id=' . $id . '&created=1', true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Der Ausweis-Draft konnte nicht angelegt werden.';
        }
    }
}

mmHeader('Neuer Verify-Ausweis', 'Projektbezogenen Verify-Ausweis anlegen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Ausweis-Service</p>
    <h1>Neuen Ausweis anlegen.</h1>
    <p class="lead">Der Datensatz wird zunächst inaktiv angelegt. Aktivierung erfolgt erst nach vollständiger Ausstattung und Prüfung.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/credentials.php">Zurück zu Ausweisen</a></div>
  </div>
</section>

<section class="section">
  <div class="form-card credential-editor">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/credential-new.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>01</span><div><strong>Person & Rolle</strong><small>Wer legitimiert sich vor Ort?</small></div></div>
        <div class="form-grid">
          <label class="wide">Ausweis-Person
            <select name="subject_user_id" required>
              <option value="">Bitte aktive Elite-Person auswählen</option>
              <?php foreach ($credentialSubjects as $subject): ?>
                <option value="<?= (int)$subject['id'] ?>"<?= (int)$values['subject_user_id'] === (int)$subject['id'] ? ' selected' : '' ?>>
                  <?= mmEscape((string)$subject['display_name'] . ' · ' . (string)$subject['member_code'] . ' · ' . (string)$subject['email']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="field-hint">Der Name auf dem Ausweis wird zwingend aus dieser Person übernommen und kann nicht frei überschrieben werden.</small>
          </label>
          <label class="wide">Rolle
            <select name="credential_role_id" required>
              <option value="">Bitte Rolle auswählen</option>
              <?php foreach ($credentialRoles as $roleOption): ?>
                <option value="<?= (int)$roleOption['id'] ?>"<?= (int)$values['credential_role_id'] === (int)$roleOption['id'] ? ' selected' : '' ?>><?= mmEscape((string)$roleOption['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </div>

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>02</span><div><strong>Projekt</strong><small>Agentur und freigegebenes Projekt</small></div></div>
        <div class="form-grid">
          <label>Agentur
            <select name="agency_id" required data-credential-agency>
              <option value="">Bitte auswählen</option>
              <?php foreach ($agencyOptions as $agencyId=>$agencyName): ?>
                <option value="<?= (int)$agencyId ?>"<?= (int)$values['agency_id'] === (int)$agencyId ? ' selected' : '' ?>><?= mmEscape($agencyName) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="wide">Projekt
            <select name="credential_project_id" required data-credential-project>
              <option value="">Bitte Projekt auswählen</option>
              <?php foreach ($credentialProjects as $projectOption): ?>
                <option value="<?= (int)$projectOption['id'] ?>"
                        data-agency-id="<?= (int)$projectOption['agency_id'] ?>"
                        <?= (int)$values['credential_project_id'] === (int)$projectOption['id'] ? 'selected' : '' ?>>
                  <?= mmEscape((string)$projectOption['project_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="field-hint">Nur Projekte aus den freigegebenen Ausweis-Stammdaten können verwendet werden.</small>
          </label>
        </div>
      </div>

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>03</span><div><strong>Gültigkeit & Sichtbarkeit</strong><small>Zeitraum und öffentliche Vertraulichkeit</small></div></div>
        <div class="form-grid">
          <label>Gültig ab<input type="date" name="valid_from" value="<?= mmEscape($values['valid_from']) ?>"></label>
          <label>Gültig bis<input type="date" name="valid_until" value="<?= mmEscape($values['valid_until']) ?>"></label>
          <label>Vertraulichkeit
            <select name="confidentiality_mode">
              <option value="public"<?= $values['confidentiality_mode'] === 'public' ? ' selected' : '' ?>>Öffentlich im Verify-Kontext</option>
              <option value="confidential"<?= $values['confidentiality_mode'] === 'confidential' ? ' selected' : '' ?>>Vertraulich</option>
            </select>
          </label>
          <div class="wide credential-photo-permission credential-photo-permission--readonly">
            <span><strong>Fotoerlaubnis kommt aus dem Projekt</strong><small class="field-hint">Wird automatisch aus den kontrollierten Projekt-Stammdaten übernommen.</small></span>
          </div>
          <label class="wide">Öffentlicher Hinweis<textarea name="public_note"><?= mmEscape($values['public_note']) ?></textarea></label>
        </div>
      </div>

      <div class="notice">
        <strong>Automatische Bindung:</strong> Profilfoto, Projektlogo, Legitimationsschreiben, Scope und Fotoerlaubnis kommen aus den geprüften Stammdaten. Nur das Agenturlogo bleibt aktuell als separate Ausweisausstattung offen.
      </div>

      <div class="elite-profile-actions"><button type="submit">Ausweis-Draft anlegen</button></div>
    </form>
  </div>
</section>
<script>
(() => {
  const agency = document.querySelector('[data-credential-agency]');
  const project = document.querySelector('[data-credential-project]');
  if (!agency || !project) return;

  const apply = () => {
    const agencyId = agency.value;
    [...project.options].forEach((option, index) => {
      if (index === 0) return;
      option.hidden = option.dataset.agencyId !== agencyId;
      if (option.hidden && option.selected) project.value = '';
    });
  };

  agency.addEventListener('change', () => { project.value = ''; apply(); });
  apply();
})();
</script>
<?php mmFooter(); ?>
