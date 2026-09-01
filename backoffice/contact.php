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

$allowedStatuses = ['new','seen','done'];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $newStatus = (string)($_POST['status'] ?? '');
        if (!in_array($newStatus, $allowedStatuses, true)) {
            $error = 'Ungültiger Status.';
        } else {
            $stmt = mmDb()->prepare('UPDATE contact_requests SET status = :status WHERE id = :id');
            $stmt->execute(['status'=>$newStatus,'id'=>$id]);
            mmBackofficeAudit((int)$user['id'], 'contact.status_changed', 'contact_request', $id, ['status'=>$newStatus]);
            header('Location: /backoffice/contact.php?id=' . $id . '&updated=1', true, 303);
            exit;
        }
    }
}

$stmt = mmDb()->prepare(
    'SELECT id, reference_code, request_type, name, organisation, email, phone, subject, message,
            privacy_acknowledged_at, created_at, status, notification_sent_at, notification_failed_at,
            confirmation_sent_at, confirmation_failed_at
     FROM contact_requests
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id'=>$id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit('Not found');
}

$contactRisk = mmBackofficeContactRisk($row);

mmHeader('Kontaktanfrage', 'Read-only Kontaktanfrage im MysteryMarket Backoffice.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Kontakt · <?= mmEscape((string)($row['reference_code'] ?: $row['id'])) ?></p>
    <h1><?= mmEscape((string)$row['subject']) ?></h1>
    <p class="lead"><?= mmEscape((string)$row['name']) ?><?= $row['organisation'] ? ' · ' . mmEscape((string)$row['organisation']) : '' ?></p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/contacts.php">Zurück zur Liste</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="card">
      <span class="badge">Kontakt</span>
      <p><strong>Name:</strong> <?= mmEscape((string)$row['name']) ?></p>
      <p><strong>Organisation:</strong> <?= mmEscape((string)($row['organisation'] ?: '—')) ?></p>
      <p><strong>E-Mail:</strong> <?= mmEscape((string)$row['email']) ?></p>
      <p><strong>Telefon:</strong> <?= mmEscape((string)($row['phone'] ?: '—')) ?></p>
      <p><strong>Typ:</strong> <?= mmEscape((string)$row['request_type']) ?></p>
      <p><strong>Eingang:</strong> <?= mmEscape((string)$row['created_at']) ?></p>
    </article>

    <article class="card">
      <span class="badge">Systemstatus</span>
      <p><strong>Interner Hinweis:</strong> <?= mmBackofficeContactRiskBadge($row) ?></p>
      <?php if (!empty($contactRisk['reasons'])): ?>
        <ul class="contact-risk-reasons">
          <?php foreach ($contactRisk['reasons'] as $reason): ?>
            <li><?= mmEscape((string)$reason) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <p><strong>Status:</strong> <?= mmBackofficeStatusBadge((string)$row['status']) ?></p>
      <p><strong>Notification:</strong> <?= mmEscape((string)($row['notification_sent_at'] ?: $row['notification_failed_at'] ?: '—')) ?></p>
      <p><strong>Bestätigung:</strong> <?= mmEscape((string)($row['confirmation_sent_at'] ?: $row['confirmation_failed_at'] ?: '—')) ?></p>
      <p><strong>Privacy acknowledged:</strong> <?= mmEscape((string)$row['privacy_acknowledged_at']) ?></p>
    </article>
  </div>
</section>

<section class="section">
  <article class="backoffice-contact-message">
    <span class="badge">Nachricht</span>
    <h2><?= mmEscape((string)$row['subject']) ?></h2>
    <p><?= nl2br(mmEscape((string)$row['message'])) ?></p>
  </article>
</section>

<section class="section">
  <div class="form-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <h2>Status</h2>
    <p class="partner-note">Nur interner Bearbeitungsstatus. Antworten sind in R1.1 ausdrücklich nicht möglich.</p>
    <form method="post" action="/backoffice/contact.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <label>Bearbeitungsstatus
        <select name="status">
          <?php foreach ($allowedStatuses as $s): ?>
            <option value="<?= mmEscape($s) ?>"<?= $row['status'] === $s ? ' selected' : '' ?>><?= mmEscape($s) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit">Status speichern</button>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
