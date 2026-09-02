<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$rows = mmDb()->query(
    'SELECT id, name, short_name, website_url, logo_asset, city, contact_name, elite_visible, is_active, created_at, updated_at
     FROM agencies
     ORDER BY is_active DESC, name ASC'
)->fetchAll();

mmHeader('Agenturen', 'Interne Agentur-Stammdaten.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Stammdaten</p>
    <h1>Agenturen.</h1>
    <p class="lead">Zentrale Agentur-Stammdaten für Feed und Freigaben.</p>
    <div class="actions">
      <a class="button" href="/backoffice/agency-new.php">Agentur anlegen</a>
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>
<section class="section">
  <?php if (!$rows): ?>
    <div class="notice"><strong>Noch keine Agenturen angelegt.</strong></div>
  <?php else: ?>
    <div class="backoffice-table-wrap">
      <table class="backoffice-table">
        <thead><tr><th>Agentur</th><th>Ort</th><th>Ansprechpartner</th><th>Elite</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>
              <a href="/backoffice/agency.php?id=<?= (int)$row['id'] ?>"><strong><?= mmEscape((string)$row['name']) ?></strong></a>
              <br><small><?= !empty($row['logo_asset']) ? 'Logo vorhanden' : 'Logo fehlt' ?><?= !empty($row['short_name']) ? ' · ' . mmEscape((string)$row['short_name']) : '' ?></small>
            </td>
            <td><?= mmEscape((string)($row['city'] ?: '—')) ?></td>
            <td><?= mmEscape((string)($row['contact_name'] ?: '—')) ?></td>
            <td><?= mmBackofficeStatusBadge((int)$row['elite_visible'] === 1 ? 'active' : 'pending', (int)$row['elite_visible'] === 1 ? 'sichtbar' : 'Admin') ?></td>
            <td><?= mmBackofficeStatusBadge((int)$row['is_active'] === 1 ? 'active' : 'disabled') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
