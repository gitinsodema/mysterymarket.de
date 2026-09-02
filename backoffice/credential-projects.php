<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

$stmt = mmDb()->query(
    "SELECT p.id, p.customer_name, p.project_name, p.scope_key, p.photo_allowed, p.is_active,
            a.name AS agency_name
     FROM credential_projects p
     JOIN agencies a ON a.id = p.agency_id
     ORDER BY p.is_active DESC, a.name, p.customer_name, p.project_name"
);
$rows = $stmt->fetchAll();

mmHeader('Ausweis-Projekte', 'Kontrollierte Projektstammdaten für Verify-Ausweise.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Ausweis-Stammdaten</p>
    <h1>Projekte.</h1>
    <p class="lead">Nur hier freigegebene Agentur-/Projektkunde-/Projekt-Kombinationen können für neue Verify-Ausweise verwendet werden.</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/credentials.php">Ausweis-Service</a>
      <a class="button" href="/backoffice/credential-project-new.php">Projekt anlegen</a>
    </div>
  </div>
</section>

<section class="section">
  <?php if (isset($_GET['created'])): ?><div class="alert success"><strong>Projekt angelegt.</strong></div><?php endif; ?>
  <?php if (isset($_GET['updated'])): ?><div class="alert success"><strong>Projektstatus gespeichert.</strong></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="backoffice-table-wrap">
    <table class="backoffice-table">
      <thead>
        <tr><th>Agentur</th><th>Projektkunde</th><th>Projekt</th><th>Foto</th><th>Scope</th><th>Status</th><th>Aktion</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= mmEscape((string)$row['agency_name']) ?></td>
          <td><?= mmEscape((string)$row['customer_name']) ?></td>
          <td><strong><?= mmEscape((string)$row['project_name']) ?></strong></td>
          <td><?= mmBackofficeStatusBadge((int)$row['photo_allowed'] === 1 ? 'active' : 'inactive', (int)$row['photo_allowed'] === 1 ? 'erlaubt' : 'nein') ?></td>
          <td><?= mmEscape((string)($row['scope_key'] ?: '—')) ?></td>
          <td><?= mmBackofficeStatusBadge((int)$row['is_active'] === 1 ? 'active' : 'inactive') ?></td>
          <td>
            <a class="button secondary" href="/backoffice/credential-project.php?id=<?= (int)$row['id'] ?>">Bearbeiten</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php mmFooter(); ?>
