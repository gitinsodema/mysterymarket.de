<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$rows = mmDb()->query(
    'SELECT l.id, l.action_key, l.entity_type, l.entity_id, l.created_at,
            u.email AS actor_email
     FROM backoffice_audit_log l
     LEFT JOIN backoffice_users u ON u.id = l.actor_user_id
     ORDER BY l.created_at DESC, l.id DESC
     LIMIT 250'
)->fetchAll();

mmHeader('Audit Log', 'Interne MysteryMarket Backoffice-Aktivitäten.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · System</p>
    <h1>Audit Log.</h1>
    <p class="lead">Die letzten 250 sicherheits- und verwaltungsrelevanten Backoffice-Ereignisse.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/">Dashboard</a></div>
  </div>
</section>
<section class="section">
  <?php if (!$rows): ?>
    <div class="notice"><strong>Noch keine Audit-Ereignisse.</strong></div>
  <?php else: ?>
    <div class="backoffice-table-wrap">
      <table class="backoffice-table">
        <thead><tr><th>Zeit</th><th>Aktion</th><th>Akteur</th><th>Entität</th><th>ID</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= mmEscape((string)$row['created_at']) ?></td>
            <td><strong><?= mmEscape((string)$row['action_key']) ?></strong></td>
            <td><?= mmEscape((string)($row['actor_email'] ?: 'System / anonym')) ?></td>
            <td><?= mmEscape((string)($row['entity_type'] ?: '—')) ?></td>
            <td><?= $row['entity_id'] !== null ? (int)$row['entity_id'] : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
