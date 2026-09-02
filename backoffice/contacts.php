<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$status = (string)($_GET['status'] ?? 'all');
$query = trim((string)($_GET['q'] ?? ''));
$allowed = ['all','new','in_progress','done'];
if (!in_array($status, $allowed, true)) {
    $status = 'all';
}

$where = [];
$params = [];

if ($query === '') {
    $where[] = "status <> 'archived'";
}

if ($status !== 'all') {
    $where[] = 'status = :status';
    $params['status'] = $status;
}

if ($query !== '') {
    $where[] = '(reference_code LIKE :query
        OR name LIKE :query
        OR organisation LIKE :query
        OR email LIKE :query
        OR subject LIKE :query
        OR message LIKE :query)';
    $params['query'] = '%' . $query . '%';
}

$sql = 'SELECT id, reference_code, request_type, name, organisation, email, phone, subject, message,
               status, moderation_decision, created_at
        FROM contact_requests';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC, id DESC';

$stmt = mmDb()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statusPriority = ['new'=>0, 'in_progress'=>1, 'done'=>2, 'archived'=>3];
$riskPriority = ['green'=>0, 'yellow'=>1, 'red'=>2];

usort($rows, static function (array $a, array $b) use ($statusPriority, $riskPriority): int {
    $aStatus = (string)($a['status'] ?? 'new');
    $bStatus = (string)($b['status'] ?? 'new');
    $statusCompare = ($statusPriority[$aStatus] ?? 9) <=> ($statusPriority[$bStatus] ?? 9);
    if ($statusCompare !== 0) {
        return $statusCompare;
    }

    if ($aStatus === 'new') {
        $aRisk = mmBackofficeContactRisk($a);
        $bRisk = mmBackofficeContactRisk($b);
        $aRank = $riskPriority[(string)($aRisk['level'] ?? 'green')] ?? 9;
        $bRank = $riskPriority[(string)($bRisk['level'] ?? 'green')] ?? 9;
        if (($a['moderation_decision'] ?? '') === 'spam') {
            $aRank = 3;
        }
        if (($b['moderation_decision'] ?? '') === 'spam') {
            $bRank = 3;
        }
        $riskCompare = $aRank <=> $bRank;
        if ($riskCompare !== 0) {
            return $riskCompare;
        }
    }

    return strcmp((string)$b['created_at'], (string)$a['created_at']);
});

mmHeader('Kontakte', 'Kontakt-Arbeitsansicht im MysteryMarket Backoffice.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Kontakte</p>
    <h1>Kontaktanfragen.</h1>
    <p class="lead">Arbeitsansicht für neue, laufende und erledigte Kontaktanfragen. Archivierte Einträge erscheinen nur über die Suche.</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>

<section class="section">
  <form class="backoffice-contact-search" method="get" action="/backoffice/contacts.php">
    <label>
      <span>Suche</span>
      <input type="search" name="q" value="<?= mmEscape($query) ?>" placeholder="Ref., Name, Organisation, E-Mail, Betreff oder Text">
    </label>
    <input type="hidden" name="status" value="<?= mmEscape($status) ?>">
    <button type="submit">Suchen</button>
    <?php if ($query !== ''): ?><a class="button secondary" href="/backoffice/contacts.php?status=<?= mmEscape($status) ?>">Zurücksetzen</a><?php endif; ?>
  </form>

  <div class="backoffice-filter-row">
    <?php
      $filterLabels = [
        'all' => 'Alle aktiv',
        'new' => 'New',
        'in_progress' => 'In Arbeit',
        'done' => 'Done',
      ];
      foreach ($allowed as $filter):
        $href = '/backoffice/contacts.php?status=' . rawurlencode($filter);
        if ($query !== '') {
            $href .= '&q=' . rawurlencode($query);
        }
    ?>
      <a class="button<?= $status === $filter ? '' : ' secondary' ?>" href="<?= mmEscape($href) ?>">
        <?= mmEscape($filterLabels[$filter]) ?>
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
            <th>Hinweis</th>
            <th>Entscheidung</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr class="<?= $row['status'] === 'done' ? 'contact-row--done' : ($row['status'] === 'archived' ? 'contact-row--archived' : '') ?>">
            <td><a href="/backoffice/contact.php?id=<?= (int)$row['id'] ?>"><strong><?= mmEscape((string)($row['reference_code'] ?: $row['id'])) ?></strong></a></td>
            <td><?= mmEscape((string)$row['created_at']) ?></td>
            <td><a href="/backoffice/contact.php?id=<?= (int)$row['id'] ?>"><?= mmEscape((string)$row['name']) ?></a></td>
            <td><?= mmEscape((string)($row['organisation'] ?: '—')) ?></td>
            <td><?= mmEscape((string)$row['request_type']) ?></td>
            <td><?= mmEscape((string)$row['subject']) ?></td>
            <td><?= mmBackofficeContactRiskBadge($row) ?></td>
            <td><?= mmBackofficeContactModerationBadge((string)$row['moderation_decision']) ?></td>
            <td><?= mmBackofficeStatusBadge((string)$row['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
