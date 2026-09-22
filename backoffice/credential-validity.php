<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$isAdmin = (string)($user['role'] ?? '') === 'admin';
$mode = (string)($_GET['mode'] ?? $_POST['mode'] ?? '');
$credentialId = (int)($_GET['id'] ?? $_POST['credential_id'] ?? 0);
$error = '';

function mmValidityCredentialFetch(int $id): ?array
{
    $stmt = mmDb()->prepare(
        'SELECT id, reference_code, person_name, role_label, agency_name, project_name,
                valid_from, valid_until, subject_user_id, is_active
         FROM audit_verifications
         WHERE id = :id
           AND is_personal_verification = 1
         LIMIT 1'
    );
    $stmt->execute(['id'=>$id]);
    return $stmt->fetch() ?: null;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        try {
            if ($action === 'admin_update_validity') {
                if (!$isAdmin) {
                    throw new RuntimeException('Nur Admins dürfen die Gültigkeit direkt ändern.');
                }

                $credential = mmValidityCredentialFetch($credentialId);
                if (!$credential || (int)$credential['is_active'] !== 1) {
                    throw new RuntimeException('Aktiver Verify-Ausweis nicht gefunden.');
                }

                $validFrom = mmCredentialDateOrNull((string)($_POST['valid_from'] ?? ''));
                $validUntil = mmCredentialDateOrNull((string)($_POST['valid_until'] ?? ''));
                if ($validUntil === null) {
                    throw new InvalidArgumentException('Gültig bis ist erforderlich.');
                }
                if ($validFrom !== null && $validUntil < $validFrom) {
                    throw new InvalidArgumentException('Das Gültigkeitsende liegt vor dem Beginn.');
                }

                $oldFrom = $credential['valid_from'] !== null ? (string)$credential['valid_from'] : null;
                $oldUntil = $credential['valid_until'] !== null ? (string)$credential['valid_until'] : null;

                $stmt = mmDb()->prepare(
                    'UPDATE audit_verifications
                     SET valid_from = :valid_from,
                         valid_until = :valid_until,
                         updated_at = NOW()
                     WHERE id = :id
                       AND is_personal_verification = 1
                       AND is_active = 1'
                );
                $stmt->execute([
                    'valid_from'=>$validFrom,
                    'valid_until'=>$validUntil,
                    'id'=>$credentialId,
                ]);

                mmBackofficeAudit(
                    (int)$user['id'],
                    'verify_credential.validity_changed',
                    'audit_verification',
                    $credentialId,
                    [
                        'reference_code'=>(string)$credential['reference_code'],
                        'old_valid_from'=>$oldFrom,
                        'old_valid_until'=>$oldUntil,
                        'new_valid_from'=>$validFrom,
                        'new_valid_until'=>$validUntil,
                    ]
                );

                header('Location: /backoffice/credential-validity.php?id=' . $credentialId . '&updated=1', true, 303);
                exit;
            }

            if ($action === 'request_extension') {
                $credential = mmValidityCredentialFetch($credentialId);
                if (!$credential
                    || (int)$credential['is_active'] !== 1
                    || (int)($credential['subject_user_id'] ?? 0) !== (int)$user['id']) {
                    throw new RuntimeException('Dieser Ausweis kann von deinem Konto nicht verlängert werden.');
                }

                $requestedUntil = mmCredentialDateOrNull((string)($_POST['requested_valid_until'] ?? ''));
                $note = trim((string)($_POST['request_note'] ?? ''));
                if ($requestedUntil === null) {
                    throw new InvalidArgumentException('Gewünschtes Gültigkeitsende ist erforderlich.');
                }
                $currentUntil = trim((string)($credential['valid_until'] ?? ''));
                if ($currentUntil !== '' && $requestedUntil <= $currentUntil) {
                    throw new InvalidArgumentException('Das gewünschte Gültigkeitsende muss nach dem aktuellen Datum liegen.');
                }

                $open = mmDb()->prepare(
                    "SELECT COUNT(*)
                     FROM credential_validity_requests
                     WHERE credential_id = :credential_id
                       AND request_status = 'pending'"
                );
                $open->execute(['credential_id'=>$credentialId]);
                if ((int)$open->fetchColumn() > 0) {
                    throw new InvalidArgumentException('Für diesen Ausweis besteht bereits eine offene Verlängerungsanfrage.');
                }

                $stmt = mmDb()->prepare(
                    "INSERT INTO credential_validity_requests
                     (credential_id, requester_user_id, requested_valid_until, request_note, request_status, created_at, updated_at)
                     VALUES (:credential_id, :requester_user_id, :requested_valid_until, :request_note, 'pending', NOW(), NOW())"
                );
                $stmt->execute([
                    'credential_id'=>$credentialId,
                    'requester_user_id'=>(int)$user['id'],
                    'requested_valid_until'=>$requestedUntil,
                    'request_note'=>$note !== '' ? $note : null,
                ]);
                $requestId = (int)mmDb()->lastInsertId();

                mmBackofficeAudit(
                    (int)$user['id'],
                    'verify_credential.validity_requested',
                    'credential_validity_request',
                    $requestId,
                    [
                        'credential_id'=>$credentialId,
                        'reference_code'=>(string)$credential['reference_code'],
                        'requested_valid_until'=>$requestedUntil,
                    ]
                );

                header('Location: /backoffice/credential-validity.php?mode=mine&requested=1', true, 303);
                exit;
            }

            if ($action === 'approve_request' || $action === 'reject_request') {
                if (!$isAdmin) {
                    throw new RuntimeException('Nur Admins dürfen Verlängerungsanfragen bearbeiten.');
                }

                $requestId = (int)($_POST['request_id'] ?? 0);
                $stmt = mmDb()->prepare(
                    "SELECT r.*, v.reference_code, v.valid_from, v.valid_until, v.is_active
                     FROM credential_validity_requests r
                     JOIN audit_verifications v ON v.id = r.credential_id
                     WHERE r.id = :id
                       AND r.request_status = 'pending'
                     LIMIT 1"
                );
                $stmt->execute(['id'=>$requestId]);
                $request = $stmt->fetch();
                if (!$request) {
                    throw new RuntimeException('Offene Verlängerungsanfrage nicht gefunden.');
                }

                if ($action === 'approve_request') {
                    if ((int)$request['is_active'] !== 1) {
                        throw new RuntimeException('Der zugehörige Ausweis ist nicht mehr aktiv.');
                    }

                    $pdo = mmDb();
                    $pdo->beginTransaction();
                    try {
                        $updateCredential = $pdo->prepare(
                            'UPDATE audit_verifications
                             SET valid_until = :valid_until, updated_at = NOW()
                             WHERE id = :id AND is_active = 1'
                        );
                        $updateCredential->execute([
                            'valid_until'=>(string)$request['requested_valid_until'],
                            'id'=>(int)$request['credential_id'],
                        ]);

                        $updateRequest = $pdo->prepare(
                            "UPDATE credential_validity_requests
                             SET request_status = 'approved', reviewed_by = :reviewed_by,
                                 reviewed_at = NOW(), updated_at = NOW()
                             WHERE id = :id AND request_status = 'pending'"
                        );
                        $updateRequest->execute([
                            'reviewed_by'=>(int)$user['id'],
                            'id'=>$requestId,
                        ]);
                        if ($updateRequest->rowCount() !== 1) {
                            throw new RuntimeException('Verlängerungsanfrage konnte nicht eindeutig freigegeben werden.');
                        }
                        $pdo->commit();
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $e;
                    }

                    mmBackofficeAudit(
                        (int)$user['id'],
                        'verify_credential.validity_request_approved',
                        'credential_validity_request',
                        $requestId,
                        [
                            'credential_id'=>(int)$request['credential_id'],
                            'reference_code'=>(string)$request['reference_code'],
                            'old_valid_until'=>$request['valid_until'],
                            'new_valid_until'=>(string)$request['requested_valid_until'],
                        ]
                    );
                } else {
                    $updateRequest = mmDb()->prepare(
                        "UPDATE credential_validity_requests
                         SET request_status = 'rejected', reviewed_by = :reviewed_by,
                             reviewed_at = NOW(), updated_at = NOW()
                         WHERE id = :id AND request_status = 'pending'"
                    );
                    $updateRequest->execute([
                        'reviewed_by'=>(int)$user['id'],
                        'id'=>$requestId,
                    ]);

                    mmBackofficeAudit(
                        (int)$user['id'],
                        'verify_credential.validity_request_rejected',
                        'credential_validity_request',
                        $requestId,
                        ['credential_id'=>(int)$request['credential_id']]
                    );
                }

                $targetId = (int)$request['credential_id'];
                header('Location: /backoffice/credential-validity.php?id=' . $targetId . '&reviewed=1', true, 303);
                exit;
            }

            throw new InvalidArgumentException('Unbekannte Aktion.');
        } catch (InvalidArgumentException|RuntimeException $e) {
            $error = $e->getMessage();
        } catch (Throwable $e) {
            $error = 'Die Gültigkeitsaktion konnte nicht gespeichert werden.';
        }
    }
}

if ($isAdmin && $credentialId > 0 && $mode !== 'mine') {
    $credential = mmValidityCredentialFetch($credentialId);
    if (!$credential) {
        http_response_code(404);
        exit('Not found');
    }

    $requestsStmt = mmDb()->prepare(
        "SELECT r.*, u.email
         FROM credential_validity_requests r
         JOIN backoffice_users u ON u.id = r.requester_user_id
         WHERE r.credential_id = :credential_id
         ORDER BY r.created_at DESC, r.id DESC"
    );
    $requestsStmt->execute(['credential_id'=>$credentialId]);
    $requests = $requestsStmt->fetchAll();

    mmHeader('Ausweis-Gültigkeit', 'Gültigkeit eines aktiven Verify-Ausweises verwalten.', 'noindex,nofollow');
    ?>
    <section class="hero backoffice-dashboard-hero"><div>
      <p class="eyebrow">Admin · Verify-Ausweis</p>
      <h1>Gültigkeit.</h1>
      <p class="lead"><?= mmEscape((string)$credential['reference_code']) ?> · <?= mmEscape((string)$credential['person_name']) ?> · <?= mmEscape((string)$credential['project_name']) ?></p>
      <div class="actions"><a class="button secondary" href="/backoffice/credentials.php">Zurück zum Ausweis-Service</a></div>
    </div></section>

    <section class="section">
      <?php if (isset($_GET['updated'])): ?><div class="alert success"><strong>Gültigkeit geändert.</strong> Verify-Referenz und Ausweisidentität bleiben unverändert.</div><?php endif; ?>
      <?php if (isset($_GET['reviewed'])): ?><div class="alert success"><strong>Verlängerungsanfrage bearbeitet.</strong></div><?php endif; ?>
      <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

      <div class="grid two">
        <div class="form-card">
          <h2>Admin: Gültigkeit direkt ändern.</h2>
          <p class="partner-note">Ändert ausschließlich Gültig-ab/Gültig-bis. Person, Projekt, Rolle, Logos und Verify-Code bleiben unverändert.</p>
          <?php if ((int)$credential['is_active'] !== 1): ?>
            <div class="notice">Der Ausweis ist nicht aktiv. Direkte Gültigkeitsänderung ist nur für aktive Ausweise vorgesehen.</div>
          <?php else: ?>
            <form method="post" action="/backoffice/credential-validity.php?id=<?= (int)$credential['id'] ?>">
              <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
              <input type="hidden" name="action" value="admin_update_validity">
              <input type="hidden" name="credential_id" value="<?= (int)$credential['id'] ?>">
              <div class="form-grid">
                <label>Gültig ab<input type="date" name="valid_from" value="<?= mmEscape((string)($credential['valid_from'] ?? '')) ?>"></label>
                <label>Gültig bis<input type="date" name="valid_until" required value="<?= mmEscape((string)($credential['valid_until'] ?? '')) ?>"></label>
              </div>
              <button type="submit">Gültigkeit speichern</button>
            </form>
          <?php endif; ?>
        </div>

        <div class="form-card">
          <h2>Verlängerungsanfragen.</h2>
          <?php if (!$requests): ?>
            <div class="notice">Keine Verlängerungsanfragen für diesen Ausweis.</div>
          <?php else: ?>
            <div class="backoffice-list">
              <?php foreach ($requests as $request): ?>
                <article>
                  <div>
                    <strong>bis <?= mmEscape((string)$request['requested_valid_until']) ?></strong>
                    <small><?= mmEscape((string)$request['email']) ?> · <?= mmEscape((string)$request['created_at']) ?></small>
                    <?php if (!empty($request['request_note'])): ?><p><?= nl2br(mmEscape((string)$request['request_note'])) ?></p><?php endif; ?>
                  </div>
                  <div class="credential-output-list-actions">
                    <?= mmBackofficeStatusBadge((string)$request['request_status']) ?>
                    <?php if ((string)$request['request_status'] === 'pending'): ?>
                      <form method="post" action="/backoffice/credential-validity.php?id=<?= (int)$credential['id'] ?>">
                        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
                        <input type="hidden" name="credential_id" value="<?= (int)$credential['id'] ?>">
                        <input type="hidden" name="request_id" value="<?= (int)$request['id'] ?>">
                        <button type="submit" name="action" value="approve_request">Übernehmen</button>
                        <button type="submit" name="action" value="reject_request" class="button secondary">Ablehnen</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php mmFooter();
    exit;
}

$mineStmt = mmDb()->prepare(
    "SELECT v.id, v.reference_code, v.project_name, v.agency_name, v.role_label,
            v.valid_from, v.valid_until, v.is_active,
            r.id AS pending_request_id, r.requested_valid_until AS pending_requested_until
     FROM audit_verifications v
     LEFT JOIN credential_validity_requests r
       ON r.credential_id = v.id AND r.request_status = 'pending'
     WHERE v.subject_user_id = :user_id
       AND v.is_personal_verification = 1
       AND v.is_active = 1
     ORDER BY v.valid_until DESC, v.id DESC"
);
$mineStmt->execute(['user_id'=>(int)$user['id']]);
$credentials = $mineStmt->fetchAll();

$pendingAdmin = [];
if ($isAdmin && $mode !== 'mine') {
    $pendingStmt = mmDb()->query(
        "SELECT r.id, r.credential_id, r.requested_valid_until, r.created_at,
                v.reference_code, v.person_name, v.project_name, v.valid_until
         FROM credential_validity_requests r
         JOIN audit_verifications v ON v.id = r.credential_id
         WHERE r.request_status = 'pending'
         ORDER BY r.created_at ASC, r.id ASC"
    );
    $pendingAdmin = $pendingStmt->fetchAll();
}

mmHeader('Ausweis verlängern', 'Verlängerung eines Verify-Ausweises beantragen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero"><div>
  <p class="eyebrow"><?= $isAdmin && $mode !== 'mine' ? 'Admin · Verlängerungsanfragen' : 'Elite Shopper · Meine Ausweise' ?></p>
  <h1><?= $isAdmin && $mode !== 'mine' ? 'Gültigkeitsanfragen.' : 'Ausweis verlängern.' ?></h1>
  <p class="lead"><?= $isAdmin && $mode !== 'mine' ? 'Offene Verlängerungsanfragen zentral prüfen.' : 'Du beantragst nur die Verlängerung. Die tatsächliche Gültigkeitsänderung bleibt eine Adminentscheidung.' ?></p>
  <div class="actions">
    <a class="button secondary" href="/backoffice/">Dashboard</a>
    <?php if ($isAdmin): ?><a class="button secondary" href="/backoffice/credentials.php">Ausweis-Service</a><?php endif; ?>
  </div>
</div></section>

<section class="section">
  <?php if (isset($_GET['requested'])): ?><div class="alert success"><strong>Verlängerung beantragt.</strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <?php if ($isAdmin && $mode !== 'mine'): ?>
    <?php if (!$pendingAdmin): ?>
      <div class="notice">Keine offenen Verlängerungsanfragen.</div>
    <?php else: ?>
      <div class="backoffice-list">
        <?php foreach ($pendingAdmin as $request): ?>
          <article>
            <div>
              <strong><?= mmEscape((string)$request['reference_code']) ?> · <?= mmEscape((string)$request['person_name']) ?></strong>
              <small><?= mmEscape((string)$request['project_name']) ?> · aktuell bis <?= mmEscape((string)($request['valid_until'] ?: 'offen')) ?> · beantragt bis <?= mmEscape((string)$request['requested_valid_until']) ?></small>
            </div>
            <a class="button secondary" href="/backoffice/credential-validity.php?id=<?= (int)$request['credential_id'] ?>">Prüfen</a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <?php if (!$credentials): ?>
      <div class="notice">Kein aktiver Verify-Ausweis vorhanden.</div>
    <?php else: ?>
      <div class="credential-certificate-list">
        <?php foreach ($credentials as $credential): ?>
          <article class="credential-certificate">
            <div class="credential-certificate-summary">
              <div class="credential-certificate-mark">ID</div>
              <div class="credential-certificate-main">
                <h3><?= mmEscape((string)$credential['project_name']) ?></h3>
                <div class="credential-certificate-meta">
                  <span><strong><?= mmEscape((string)$credential['reference_code']) ?></strong><small>Verify-Referenz</small></span>
                  <span><strong><?= mmEscape((string)($credential['valid_until'] ?: 'offen')) ?></strong><small>Aktuell gültig bis</small></span>
                </div>
              </div>
            </div>
            <?php if (!empty($credential['pending_request_id'])): ?>
              <div class="notice">Verlängerung bis <strong><?= mmEscape((string)$credential['pending_requested_until']) ?></strong> ist bereits beantragt.</div>
            <?php else: ?>
              <form method="post" action="/backoffice/credential-validity.php?mode=mine" class="form-card compact-admin-form">
                <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
                <input type="hidden" name="action" value="request_extension">
                <input type="hidden" name="mode" value="mine">
                <input type="hidden" name="credential_id" value="<?= (int)$credential['id'] ?>">
                <div class="form-grid">
                  <label>Gewünscht gültig bis<input type="date" name="requested_valid_until" required min="<?= mmEscape((string)($credential['valid_until'] ?: date('Y-m-d'))) ?>"></label>
                  <label class="wide">Hinweis an Admin<textarea name="request_note" placeholder="Optional, z. B. Projekt wurde verlängert."></textarea></label>
                </div>
                <button type="submit">Verlängerung beantragen</button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
