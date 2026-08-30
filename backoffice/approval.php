<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit('Not found');
}

$statuses = ['draft','requested','approved','rejected','expired'];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $status = (string)($_POST['approval_status'] ?? '');
        $evidence = trim((string)($_POST['evidence_reference'] ?? ''));
        $note = trim((string)($_POST['internal_note'] ?? ''));

        if (!in_array($status, $statuses, true)) {
            $error = 'Ungültiger Freigabestatus.';
        } else {
            $stmt = mmDb()->prepare(
                'UPDATE agency_approvals
                 SET approval_status = :status,
                     requested_at = CASE
                         WHEN :status = \'requested\' AND requested_at IS NULL THEN NOW()
                         ELSE requested_at
                     END,
                     responded_at = CASE
                         WHEN :status IN (\'approved\',\'rejected\') THEN COALESCE(responded_at, NOW())
                         ELSE responded_at
                     END,
                     evidence_reference = :evidence,
                     internal_note = :note,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'status'=>$status,
                'evidence'=>$evidence !== '' ? $evidence : null,
                'note'=>$note !== '' ? $note : null,
                'id'=>$id,
            ]);

            mmBackofficeAudit((int)$user['id'], 'agency_approval.updated', 'agency_approval', $id, [
                'status'=>$status,
                'evidence_reference'=>$evidence !== '' ? $evidence : null,
            ]);

            header('Location: /backoffice/approval.php?id=' . $id . '&updated=1', true, 303);
            exit;
        }
    }
}

$stmt = mmDb()->prepare(
    'SELECT id, agency_name, contact_name, contact_email, purpose, approval_status,
            requested_at, responded_at, evidence_reference, internal_note, created_at, updated_at
     FROM agency_approvals
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id'=>$id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit('Not found');
}

mmHeader('Agentur-Freigabe', 'Interner Freigabevorgang.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Kommunikation · <?= mmEscape((string)$row['agency_name']) ?></p>
    <h1><?= mmEscape((string)$row['purpose']) ?></h1>
    <p class="lead">Status und Nachweis der internen Freigabe.</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/approvals.php">Zurück zur Übersicht</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="card">
      <span class="badge">Agentur</span>
      <h2><?= mmEscape((string)$row['agency_name']) ?></h2>
      <p><strong>Ansprechpartner:</strong> <?= mmEscape((string)($row['contact_name'] ?: '—')) ?></p>
      <p><strong>E-Mail:</strong> <?= mmEscape((string)($row['contact_email'] ?: '—')) ?></p>
    </article>
    <article class="card">
      <span class="badge">Status</span>
      <h2><?= mmBackofficeStatusBadge((string)$row['approval_status']) ?></h2>
      <p><strong>Angefragt:</strong> <?= mmEscape((string)($row['requested_at'] ?: '—')) ?></p>
      <p><strong>Antwort:</strong> <?= mmEscape((string)($row['responded_at'] ?: '—')) ?></p>
      <p><strong>Erstellt:</strong> <?= mmEscape((string)$row['created_at']) ?></p>
    </article>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <h2>Vorgang aktualisieren</h2>
    <form method="post" action="/backoffice/approval.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <label>Status
        <select name="approval_status">
          <?php foreach ($statuses as $status): ?>
            <option value="<?= mmEscape($status) ?>"<?= $row['approval_status'] === $status ? ' selected' : '' ?>><?= mmEscape($status) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Evidenz / Referenz
        <input name="evidence_reference" maxlength="500" value="<?= mmEscape((string)($row['evidence_reference'] ?? '')) ?>" placeholder="z. B. Maildatum, Dateireferenz oder interner Pfad">
      </label>
      <label>Interne Notiz
        <textarea name="internal_note"><?= mmEscape((string)($row['internal_note'] ?? '')) ?></textarea>
      </label>
      <button type="submit">Speichern</button>
    </form>
    <p class="partner-note">Dieser Bereich dokumentiert nur den Vorgang. E-Mails werden hier in R1.1 nicht versendet.</p>
  </div>
</section>
<?php mmFooter(); ?>
