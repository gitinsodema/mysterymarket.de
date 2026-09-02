<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $active = ($_POST['active'] ?? '') === '1' ? 1 : 0;
        if ($id < 1) {
            $error = 'Ungültiges Projekt.';
        } else {
            $stmt = mmDb()->prepare(
                'UPDATE credential_projects SET is_active = :active, updated_at = NOW() WHERE id = :id'
            );
            $stmt->execute(['active'=>$active,'id'=>$id]);
            mmBackofficeAudit(
                (int)$user['id'],
                'credential_project.status_changed',
                'credential_project',
                $id,
                ['is_active'=>$active]
            );
            header('Location: /backoffice/credential-projects.php?updated=1', true, 303);
            exit;
        }
    }
}

$stmt = mmDb()->query(
    "SELECT p.id, p.customer_name, p.project_name, p.scope_key, p.is_active,
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
        <tr><th>Agentur</th><th>Projektkunde</th><th>Projekt</th><th>Scope</th><th>Status</th><th>Aktion</th></tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= mmEscape((string)$row['agency_name']) ?></td>
          <td><?= mmEscape((string)$row['customer_name']) ?></td>
          <td><strong><?= mmEscape((string)$row['project_name']) ?></strong></td>
          <td><?= mmEscape((string)($row['scope_key'] ?: '—')) ?></td>
          <td><?= mmBackofficeStatusBadge((int)$row['is_active'] === 1 ? 'active' : 'inactive') ?></td>
          <td>
            <form method="post" action="/backoffice/credential-projects.php" class="compact-inline-form">
              <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <input type="hidden" name="active" value="<?= (int)$row['is_active'] === 1 ? '0' : '1' ?>">
              <button type="submit" class="button secondary"><?= (int)$row['is_active'] === 1 ? 'Deaktivieren' : 'Aktivieren' ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php mmFooter(); ?>
