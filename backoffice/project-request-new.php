<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('elite');
$error = '';

$memberStmt = mmDb()->prepare(
    "SELECT m.id, m.member_code, m.display_name
     FROM elite_members m
     WHERE m.user_id = :user_id
       AND m.membership_status = 'active'
     LIMIT 1"
);
$memberStmt->execute(['user_id'=>(int)$user['id']]);
$member = $memberStmt->fetch();
if (!$member) {
    http_response_code(403);
    exit('Active Elite membership required');
}

$agencies = mmDb()->query(
    "SELECT id, name FROM agencies WHERE is_active = 1 ORDER BY name"
)->fetchAll();

$values = ['agency_id'=>'','project_name'=>''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (array_keys($values) as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }

    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        try {
            $agencyId = (int)$values['agency_id'];
            $projectName = $values['project_name'];

            if ($agencyId < 1) {
                throw new InvalidArgumentException('Agentur ist erforderlich.');
            }
            if ($projectName === '' || mb_strlen($projectName) > 200) {
                throw new InvalidArgumentException('Projekt ist erforderlich.');
            }

            $agencyStmt = mmDb()->prepare('SELECT id FROM agencies WHERE id = :id AND is_active = 1 LIMIT 1');
            $agencyStmt->execute(['id'=>$agencyId]);
            if (!$agencyStmt->fetchColumn()) {
                throw new InvalidArgumentException('Die ausgewählte Agentur ist nicht verfügbar.');
            }

            $duplicate = mmDb()->prepare(
                "SELECT COUNT(*)
                 FROM credential_project_requests
                 WHERE member_id = :member_id
                   AND agency_id = :agency_id
                   AND project_name = :project_name
                   AND request_status = 'pending'"
            );
            $duplicate->execute([
                'member_id'=>(int)$member['id'],
                'agency_id'=>$agencyId,
                'project_name'=>$projectName,
            ]);
            if ((int)$duplicate->fetchColumn() > 0) {
                throw new InvalidArgumentException('Für dieses Projekt besteht bereits eine offene Anfrage.');
            }

            $document = mmCredentialStoreProjectDocument(
                $_FILES['authorization_document'] ?? [],
                'project_request_' . (int)$member['id']
            );

            $stmt = mmDb()->prepare(
                "INSERT INTO credential_project_requests
                 (member_id, agency_id, project_name, authorization_document_asset, request_status, created_at, updated_at)
                 VALUES (:member_id, :agency_id, :project_name, :document, 'pending', NOW(), NOW())"
            );
            $stmt->execute([
                'member_id'=>(int)$member['id'],
                'agency_id'=>$agencyId,
                'project_name'=>$projectName,
                'document'=>$document,
            ]);
            $id = (int)mmDb()->lastInsertId();

            mmBackofficeAudit(
                (int)$user['id'],
                'credential_project_request.created',
                'credential_project_request',
                $id,
                ['agency_id'=>$agencyId,'project_name'=>$projectName]
            );

            header('Location: /backoffice/project-request-new.php?created=1', true, 303);
            exit;
        } catch (InvalidArgumentException|RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$historyStmt = mmDb()->prepare(
    "SELECT r.id, r.project_name, r.request_status, r.created_at, a.name AS agency_name
     FROM credential_project_requests r
     JOIN agencies a ON a.id = r.agency_id
     WHERE r.member_id = :member_id
     ORDER BY r.created_at DESC
     LIMIT 20"
);
$historyStmt->execute(['member_id'=>(int)$member['id']]);
$requests = $historyStmt->fetchAll();

mmHeader('Projekt vorschlagen', 'Projektanfrage für Elite Shopper.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Elite Shopper · Projektanfrage</p>
    <h1>Projekt vorschlagen.</h1>
    <p class="lead">Neue Projekte werden erst nach interner Prüfung für Verify-Ausweise freigegeben.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/">Dashboard</a></div>
  </div>
</section>

<section class="section">
  <div class="form-card compact-admin-form">
    <?php if (isset($_GET['created'])): ?><div class="alert success"><strong>Projektanfrage gesendet.</strong> Der Admin prüft Agentur, Projekt und Legitimationsschreiben.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

    <form method="post" action="/backoffice/project-request-new.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <div class="form-grid">
        <label>Agentur
          <select name="agency_id" required>
            <option value="">Bitte auswählen</option>
            <?php foreach ($agencies as $agency): ?>
              <option value="<?= (int)$agency['id'] ?>"<?= (int)$values['agency_id'] === (int)$agency['id'] ? ' selected' : '' ?>><?= mmEscape((string)$agency['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Projekt
          <input name="project_name" maxlength="200" required value="<?= mmEscape($values['project_name']) ?>" placeholder="z. B. ALDO">
        </label>
        <label class="wide">Legitimationsschreiben
          <input type="file" name="authorization_document" accept="application/pdf" required>
          <small class="field-hint">PDF bis 10 MB. Ohne Legitimationsschreiben kann kein neues Projekt vorgeschlagen werden.</small>
        </label>
      </div>
      <div class="elite-profile-actions"><button type="submit">Projektanfrage senden</button></div>
    </form>
  </div>
</section>

<?php if ($requests): ?>
<section class="section">
  <div class="form-card">
    <h2>Meine Projektanfragen.</h2>
    <div class="backoffice-list">
      <?php foreach ($requests as $request): ?>
        <article>
          <div>
            <strong><?= mmEscape((string)$request['project_name']) ?></strong>
            <small><?= mmEscape((string)$request['agency_name']) ?> · <?= mmEscape((string)$request['created_at']) ?></small>
          </div>
          <?= mmBackofficeStatusBadge((string)$request['request_status']) ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php mmFooter(); ?>
