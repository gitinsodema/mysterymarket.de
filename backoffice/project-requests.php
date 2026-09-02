<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$status = (string)($_GET['status'] ?? 'pending');
$allowed = ['pending','approved','rejected','all'];
if (!in_array($status, $allowed, true)) {
    $status = 'pending';
}

$sql = "SELECT r.id, r.project_name, r.request_status, r.created_at,
               a.name AS agency_name, m.display_name, m.member_code
        FROM credential_project_requests r
        JOIN agencies a ON a.id = r.agency_id
        JOIN elite_members m ON m.id = r.member_id";
$params = [];
if ($status !== 'all') {
    $sql .= ' WHERE r.request_status = :status';
    $params['status'] = $status;
}
$sql .= ' ORDER BY r.created_at DESC, r.id DESC';

$stmt = mmDb()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

mmHeader('Projektanfragen', 'Elite-Projektanfragen prüfen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Projekte</p>
    <h1>Projektanfragen.</h1>
    <p class="lead">Elite Shopper liefern Agentur, Projekt und Legitimationsschreiben. Freigabe und Stammdaten bleiben Admin-Sache.</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/credential-projects.php">Projekte</a>
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>
<section class="section">
  <div class="backoffice-filter-row">
    <?php foreach (['pending'=>'Offen','approved'=>'Freigegeben','rejected'=>'Abgelehnt','all'=>'Alle'] as $key=>$label): ?>
      <a class="button<?= $status === $key ? '' : ' secondary' ?>" href="/backoffice/project-requests.php?status=<?= mmEscape($key) ?>"><?= mmEscape($label) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$rows): ?>
    <div class="notice">Keine Projektanfragen in diesem Filter.</div>
  <?php else: ?>
    <div class="backoffice-table-wrap">
      <table class="backoffice-table">
        <thead><tr><th>Datum</th><th>Shopper</th><th>Agentur</th><th>Projekt</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td><?= mmEscape((string)$row['created_at']) ?></td>
              <td><?= mmEscape((string)$row['display_name']) ?><br><small><?= mmEscape((string)$row['member_code']) ?></small></td>
              <td><?= mmEscape((string)$row['agency_name']) ?></td>
              <td><strong><?= mmEscape((string)$row['project_name']) ?></strong></td>
              <td><?= mmBackofficeStatusBadge((string)$row['request_status']) ?></td>
              <td><a class="button secondary" href="/backoffice/project-request.php?id=<?= (int)$row['id'] ?>">Prüfen</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
