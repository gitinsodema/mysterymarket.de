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
    exit('Not found');
}

function mmProjectRequestAdminFetch(int $id): ?array
{
    $stmt = mmDb()->prepare(
        "SELECT r.*, a.name AS agency_name,
                m.display_name, m.member_code, m.id AS elite_member_id, m.profile_photo_asset
         FROM credential_project_requests r
         JOIN agencies a ON a.id = r.agency_id
         JOIN elite_members m ON m.id = r.member_id
         WHERE r.id = :id
         LIMIT 1"
    );
    $stmt->execute(['id'=>$id]);
    return $stmt->fetch() ?: null;
}

$request = mmProjectRequestAdminFetch($id);
if (!$request) {
    http_response_code(404);
    exit('Not found');
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } elseif ((string)$request['request_status'] !== 'pending') {
        $error = 'Diese Projektanfrage wurde bereits bearbeitet.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $adminNote = trim((string)($_POST['admin_note'] ?? ''));

        try {
            if ($action === 'approve') {
                $pdo = mmDb();
                $pdo->beginTransaction();

                try {
                    $existingStmt = $pdo->prepare(
                        "SELECT id, authorization_document_asset
                         FROM credential_projects
                         WHERE agency_id = :agency_id
                           AND project_name = :project_name
                         LIMIT 1"
                    );
                    $existingStmt->execute([
                        'agency_id'=>(int)$request['agency_id'],
                        'project_name'=>(string)$request['project_name'],
                    ]);
                    $existing = $existingStmt->fetch();

                    if ($existing) {
                        $projectId = (int)$existing['id'];
                        if (trim((string)($existing['authorization_document_asset'] ?? '')) === '') {
                            $bindDoc = $pdo->prepare(
                                "UPDATE credential_projects
                                 SET authorization_document_asset = :asset,
                                     authorization_document_label = 'Offizielles Legitimationsschreiben',
                                     updated_at = NOW()
                                 WHERE id = :id"
                            );
                            $bindDoc->execute([
                                'asset'=>(string)$request['authorization_document_asset'],
                                'id'=>$projectId,
                            ]);
                        }
                    } else {
                        $insert = $pdo->prepare(
                            "INSERT INTO credential_projects
                             (agency_id, customer_name, project_name,
                              authorization_document_asset, authorization_document_label,
                              scope_key, photo_allowed, is_active, created_at, updated_at)
                             VALUES
                             (:agency_id, :project_name, :project_name,
                              :document, 'Offizielles Legitimationsschreiben',
                              NULL, 0, 0, NOW(), NOW())"
                        );
                        $insert->execute([
                            'agency_id'=>(int)$request['agency_id'],
                            'project_name'=>(string)$request['project_name'],
                            'document'=>(string)$request['authorization_document_asset'],
                        ]);
                        $projectId = (int)$pdo->lastInsertId();
                    }

                    $update = $pdo->prepare(
                        "UPDATE credential_project_requests
                         SET request_status = 'approved',
                             admin_note = :admin_note,
                             reviewed_by = :reviewed_by,
                             reviewed_at = NOW(),
                             approved_project_id = :project_id,
                             updated_at = NOW()
                         WHERE id = :id
                           AND request_status = 'pending'"
                    );
                    $update->execute([
                        'admin_note'=>$adminNote !== '' ? $adminNote : null,
                        'reviewed_by'=>(int)$user['id'],
                        'project_id'=>$projectId,
                        'id'=>$id,
                    ]);

                    if ($update->rowCount() !== 1) {
                        throw new RuntimeException('Projektanfrage konnte nicht eindeutig freigegeben werden.');
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
                    'credential_project_request.approved',
                    'credential_project_request',
                    $id,
                    ['project_id'=>$projectId]
                );

                header('Location: /backoffice/credential-project.php?id=' . $projectId . '&from_request=1', true, 303);
                exit;
            }

            if ($action === 'reject') {
                $stmt = mmDb()->prepare(
                    "UPDATE credential_project_requests
                     SET request_status = 'rejected',
                         admin_note = :admin_note,
                         reviewed_by = :reviewed_by,
                         reviewed_at = NOW(),
                         updated_at = NOW()
                     WHERE id = :id
                       AND request_status = 'pending'"
                );
                $stmt->execute([
                    'admin_note'=>$adminNote !== '' ? $adminNote : null,
                    'reviewed_by'=>(int)$user['id'],
                    'id'=>$id,
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('Projektanfrage konnte nicht eindeutig abgelehnt werden.');
                }

                mmBackofficeAudit(
                    (int)$user['id'],
                    'credential_project_request.rejected',
                    'credential_project_request',
                    $id
                );

                header('Location: /backoffice/project-requests.php?status=pending', true, 303);
                exit;
            }

            throw new InvalidArgumentException('Unbekannte Aktion.');
        } catch (InvalidArgumentException|RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$request = mmProjectRequestAdminFetch($id);

mmHeader('Projektanfrage prüfen', 'Elite-Projektanfrage prüfen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Projektanfrage</p>
    <h1><?= mmEscape((string)$request['project_name']) ?></h1>
    <p class="lead"><?= mmEscape((string)$request['agency_name']) ?> · <?= mmEscape((string)$request['display_name']) ?></p>
    <div class="actions"><a class="button secondary" href="/backoffice/project-requests.php">Zurück zu Anfragen</a></div>
  </div>
</section>

<section class="section">
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="grid two">
    <article class="card">
      <span class="badge">Antragsteller</span>
      <div class="project-request-person">
        <?php if (!empty($request['profile_photo_asset'])): ?>
          <img src="/backoffice/member-photo.php?member_id=<?= (int)$request['elite_member_id'] ?>" alt="">
        <?php endif; ?>
        <div>
          <h3><?= mmEscape((string)$request['display_name']) ?></h3>
          <p><?= mmEscape((string)$request['member_code']) ?></p>
        </div>
      </div>
    </article>

    <article class="card">
      <span class="badge">Projekt</span>
      <h3><?= mmEscape((string)$request['project_name']) ?></h3>
      <p><strong>Agentur:</strong> <?= mmEscape((string)$request['agency_name']) ?></p>
      <p><?= mmBackofficeStatusBadge((string)$request['request_status']) ?></p>
      <a class="button secondary" target="_blank" rel="noopener" href="/backoffice/project-request-asset.php?id=<?= (int)$request['id'] ?>">Legitimationsschreiben öffnen</a>
    </article>
  </div>
</section>

<?php if ((string)$request['request_status'] === 'pending'): ?>
<section class="section">
  <div class="form-card compact-admin-form">
    <h2>Adminentscheidung.</h2>
    <p class="partner-note">Freigabe erzeugt bzw. verknüpft einen zunächst inaktiven Projektstammsatz. Logo, Scope und Fotoerlaubnis werden danach separat geprüft.</p>
    <form method="post" action="/backoffice/project-request.php?id=<?= $id ?>">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <label>Interner Hinweis<textarea name="admin_note"></textarea></label>
      <div class="credential-lifecycle-actions">
        <button type="submit" name="action" value="approve">Projektanfrage freigeben</button>
        <button type="submit" name="action" value="reject" class="button secondary">Ablehnen</button>
      </div>
    </form>
  </div>
</section>
<?php endif; ?>

<?php mmFooter(); ?>
