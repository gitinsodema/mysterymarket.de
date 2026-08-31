<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

$subjectStmt = mmDb()->query(
    "SELECT u.id, u.email, u.role, m.display_name, m.member_code
     FROM backoffice_users u
     LEFT JOIN elite_members m ON m.user_id = u.id
     ORDER BY u.role, COALESCE(m.display_name, u.email), u.email"
);
$credentialSubjects = $subjectStmt->fetchAll();

$values = [
    'subject_user_id'=>'',
    'person_name'=>'',
    'role_label'=>'Independent Field Auditor',
    'agency_name'=>'',
    'project_name'=>'',
    'brand_name'=>'',
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

            if ($subjectUserId < 1) {
                throw new InvalidArgumentException('Private Ausweis-Person ist erforderlich.');
            }
            $subjectCheck = mmDb()->prepare('SELECT id FROM backoffice_users WHERE id = :id LIMIT 1');
            $subjectCheck->execute(['id'=>$subjectUserId]);
            if (!$subjectCheck->fetchColumn()) {
                throw new InvalidArgumentException('Die ausgewählte Ausweis-Person ist nicht verfügbar.');
            }

            if ($values['person_name'] === '' || mb_strlen($values['person_name']) > 150) {
                throw new InvalidArgumentException('Person ist erforderlich.');
            }
            if ($values['role_label'] === '' || mb_strlen($values['role_label']) > 120) {
                throw new InvalidArgumentException('Rolle ist erforderlich.');
            }
            foreach (['agency_name'=>'Agentur','project_name'=>'Projekt','brand_name'=>'Marke / Kunde'] as $field=>$label) {
                if ($values[$field] === '' || mb_strlen($values[$field]) > 200) {
                    throw new InvalidArgumentException($label . ' ist erforderlich.');
                }
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
                  person_name, role_label, agency_name, project_name, brand_name,
                  photo_asset, brand_logo_asset, agency_logo_asset, scope_key,
                  document_asset, document_label, document_enabled,
                  print_card_enabled, subject_user_id, is_personal_verification, is_active,
                  created_at, updated_at)
                 VALUES
                 (:reference_code, :public_title, :public_partner, :public_client,
                  :valid_from, :valid_until, :confidentiality_mode, :public_note,
                  :person_name, :role_label, :agency_name, :project_name, :brand_name,
                  NULL, NULL, NULL, NULL,
                  NULL, NULL, 0,
                  1, :subject_user_id, 1, 0,
                  NOW(), NOW())"
            );
            $stmt->execute([
                'subject_user_id'=>$subjectUserId,
                'reference_code'=>$reference,
                'public_title'=>$values['project_name'],
                'public_partner'=>$values['agency_name'],
                'public_client'=>$values['brand_name'],
                'valid_from'=>$validFrom,
                'valid_until'=>$validUntil,
                'confidentiality_mode'=>$values['confidentiality_mode'],
                'public_note'=>$values['public_note'] !== '' ? $values['public_note'] : null,
                'person_name'=>$values['person_name'],
                'role_label'=>$values['role_label'],
                'agency_name'=>$values['agency_name'],
                'project_name'=>$values['project_name'],
                'brand_name'=>$values['brand_name'],
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
          <label class="wide">Private Ausweis-Person
            <select name="subject_user_id" required>
              <option value="">Bitte zuordnen</option>
              <?php foreach ($credentialSubjects as $subject): ?>
                <?php
                  $subjectLabel = trim((string)($subject['display_name'] ?? ''));
                  if ($subjectLabel === '') {
                      $subjectLabel = (string)$subject['email'];
                  }
                  $subjectMeta = strtoupper((string)$subject['role'])
                      . (!empty($subject['member_code']) ? ' · ' . (string)$subject['member_code'] : '')
                      . ' · ' . (string)$subject['email'];
                ?>
                <option value="<?= (int)$subject['id'] ?>"<?= (int)$values['subject_user_id'] === (int)$subject['id'] ? ' selected' : '' ?>>
                  <?= mmEscape($subjectLabel . ' — ' . $subjectMeta) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="field-hint">Steuert ausschließlich private Ausweis-/Wallet-Funktionen; Verify bleibt öffentlich getrennt.</small>
          </label>
          <label>Person<input name="person_name" maxlength="150" required value="<?= mmEscape($values['person_name']) ?>"></label>
          <label>Rolle<input name="role_label" maxlength="120" required value="<?= mmEscape($values['role_label']) ?>"></label>
        </div>
      </div>

      <div class="credential-editor-section">
        <div class="credential-editor-head"><span>02</span><div><strong>Projekt</strong><small>Agentur, Auftrag und Marke/Kunde</small></div></div>
        <div class="form-grid">
          <label>Agentur<input name="agency_name" maxlength="200" required value="<?= mmEscape($values['agency_name']) ?>"></label>
          <label>Marke / Kunde<input name="brand_name" maxlength="200" required value="<?= mmEscape($values['brand_name']) ?>"></label>
          <label class="wide">Projekt<input name="project_name" maxlength="200" required value="<?= mmEscape($values['project_name']) ?>"></label>
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
          <label class="wide">Öffentlicher Hinweis<textarea name="public_note"><?= mmEscape($values['public_note']) ?></textarea></label>
        </div>
      </div>

      <div class="notice">
        <strong>Nach dem Anlegen:</strong> Foto, Markenlogo, Agenturlogo, Projektdokument und Scope werden über die bestehende geschützte Verify-Asset-Struktur ergänzt. Der Draft bleibt bis dahin inaktiv.
      </div>

      <div class="elite-profile-actions"><button type="submit">Ausweis-Draft anlegen</button></div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
