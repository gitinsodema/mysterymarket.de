<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$status = (string)($_GET['status'] ?? 'all');
$allowed = ['all','draft','requested','approved','rejected','expired'];
if (!in_array($status, $allowed, true)) {
    $status = 'all';
}

$sql = 'SELECT id, agency_name, contact_name, purpose, approval_status, requested_at, responded_at, created_at
        FROM agency_approvals';
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE approval_status = :status';
    $params['status'] = $status;
}
$sql .= ' ORDER BY updated_at DESC, id DESC';

$stmt = mmDb()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

mmHeader('Agentur-Freigaben', 'Interne Freigabe- und Kommunikationsübersicht.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Kommunikation</p>
    <h1>Agentur-Freigaben.</h1>
    <p class="lead">Logo-, Nutzungs- und Projektfreigaben strukturiert nachhalten.</p>
    <div class="actions">
      <a class="button" href="/backoffice/approval-new.php">Vorgang anlegen</a>
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="backoffice-filter-row">
    <?php foreach ($allowed as $filter): ?>
      <a class="button<?= $status === $filter ? '' : ' secondary' ?>" href="/backoffice/approvals.php?status=<?= mmEscape($filter) ?>">
        <?= mmEscape($filter === 'all' ? 'Alle' : ucfirst($filter)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="notice"><strong>Keine Freigabevorgänge in diesem Filter.</strong></div>
  <?php else: ?>
    <div class="backoffice-table-wrap">
      <table class="backoffice-table">
        <thead><tr><th>Agentur</th><th>Ansprechpartner</th><th>Zweck</th><th>Status</th><th>Anfrage</th><th>Antwort</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><a href="/backoffice/approval.php?id=<?= (int)$row['id'] ?>"><strong><?= mmEscape((string)$row['agency_name']) ?></strong></a></td>
            <td><?= mmEscape((string)($row['contact_name'] ?: '—')) ?></td>
            <td><?= mmEscape((string)$row['purpose']) ?></td>
            <td><span class="status"><?= mmEscape((string)$row['approval_status']) ?></span></td>
            <td><?= mmEscape((string)($row['requested_at'] ?: '—')) ?></td>
            <td><?= mmEscape((string)($row['responded_at'] ?: '—')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
