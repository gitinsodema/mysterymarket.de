<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$status = (string)($_GET['status'] ?? 'all');
$allowed = ['all','new','seen','done'];
if (!in_array($status, $allowed, true)) {
    $status = 'all';
}

$sql = 'SELECT id, reference_code, request_type, name, organisation, email, subject, status, created_at
        FROM contact_requests';
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE status = :status';
    $params['status'] = $status;
}
$sql .= ' ORDER BY created_at DESC, id DESC';

$stmt = mmDb()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

mmHeader('Kontakte', 'Read-only Kontaktanfragen im MysteryMarket Backoffice.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Kontakte</p>
    <h1>Kontaktanfragen.</h1>
    <p class="lead">Read-only Sicht auf die über das öffentliche Kontaktformular gespeicherten Anfragen.</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="backoffice-filter-row">
    <?php foreach ($allowed as $filter): ?>
      <a class="button<?= $status === $filter ? '' : ' secondary' ?>" href="/backoffice/contacts.php?status=<?= mmEscape($filter) ?>">
        <?= mmEscape($filter === 'all' ? 'Alle' : ucfirst($filter)) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="notice"><strong>Keine Kontaktanfragen in diesem Filter.</strong></div>
  <?php else: ?>
    <div class="backoffice-table-wrap">
      <table class="backoffice-table">
        <thead>
          <tr>
            <th>Ref.</th>
            <th>Datum</th>
            <th>Name</th>
            <th>Organisation</th>
            <th>Typ</th>
            <th>Betreff</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><a href="/backoffice/contact.php?id=<?= (int)$row['id'] ?>"><strong><?= mmEscape((string)($row['reference_code'] ?: $row['id'])) ?></strong></a></td>
            <td><?= mmEscape((string)$row['created_at']) ?></td>
            <td><a href="/backoffice/contact.php?id=<?= (int)$row['id'] ?>"><?= mmEscape((string)$row['name']) ?></a></td>
            <td><?= mmEscape((string)($row['organisation'] ?: '—')) ?></td>
            <td><?= mmEscape((string)$row['request_type']) ?></td>
            <td><?= mmEscape((string)$row['subject']) ?></td>
            <td><?= mmBackofficeStatusBadge((string)$row['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
