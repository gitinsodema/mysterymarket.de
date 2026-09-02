<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit('Not found');
}

function mmCredentialProjectAdminFetch(int $id): ?array
{
    $stmt = mmDb()->prepare(
        "SELECT p.*, a.name AS agency_name
         FROM credential_projects p
         JOIN agencies a ON a.id = p.agency_id
         WHERE p.id = :id
         LIMIT 1"
    );
    $stmt->execute(['id'=>$id]);
    return $stmt->fetch() ?: null;
}

$project = mmCredentialProjectAdminFetch($id);
if (!$project) {
    http_response_code(404);
    exit('Not found');
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $scopeKey = trim((string)($_POST['scope_key'] ?? ''));
        $photoAllowed = ($_POST['photo_allowed'] ?? '') === '1';
        $isActive = ($_POST['is_active'] ?? '') === '1';

        $allowedScopes = ['', 'vodafone_skopos_2026', 'hp_bare_retail_2025_2026'];
        if (!in_array($scopeKey, $allowedScopes, true)) {
            $error = 'Ungültiger Verify-Scope.';
        } else {
            $stmt = mmDb()->prepare(
                'UPDATE credential_projects
                 SET scope_key = :scope_key,
                     photo_allowed = :photo_allowed,
                     is_active = :is_active,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'scope_key'=>$scopeKey !== '' ? $scopeKey : null,
                'photo_allowed'=>$photoAllowed ? 1 : 0,
                'is_active'=>$isActive ? 1 : 0,
                'id'=>$id,
            ]);

            mmBackofficeAudit(
                (int)$user['id'],
                'credential_project.updated',
                'credential_project',
                $id,
                [
                    'scope_key'=>$scopeKey !== '' ? $scopeKey : null,
                    'photo_allowed'=>$photoAllowed,
                    'is_active'=>$isActive,
                ]
            );

            header('Location: /backoffice/credential-project.php?id=' . $id . '&updated=1', true, 303);
            exit;
        }
    }
}

$project = mmCredentialProjectAdminFetch($id);

mmHeader('Ausweis-Projekt', 'Kontrollierte Projektstammdaten verwalten.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Ausweis-Stammdaten</p>
    <h1><?= mmEscape((string)$project['project_name']) ?></h1>
    <p class="lead"><?= mmEscape((string)$project['agency_name']) ?> · <?= mmEscape((string)$project['customer_name']) ?></p>
    <div class="actions"><a class="button secondary" href="/backoffice/credential-projects.php">Zurück zu Projekten</a></div>
  </div>
</section>

<section class="section">
  <div class="form-card compact-admin-form">
    <?php if (isset($_GET['updated'])): ?><div class="alert success"><strong>Projektstammdaten gespeichert.</strong></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

    <div class="credential-master-identity">
      <div><small>Agentur</small><strong><?= mmEscape((string)$project['agency_name']) ?></strong></div>
      <div><small>Projektkunde</small><strong><?= mmEscape((string)$project['customer_name']) ?></strong></div>
      <div><small>Projekt</small><strong><?= mmEscape((string)$project['project_name']) ?></strong></div>
    </div>

    <p class="field-hint">Identitätsfelder sind nach Anlage bewusst unveränderlich. Bei einer falschen Kombination bitte deaktivieren und einen neuen Stammdatensatz anlegen.</p>

    <form method="post" action="/backoffice/credential-project.php?id=<?= $id ?>">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= $id ?>">

      <div class="form-grid">
        <label class="wide credential-photo-permission">
          <input type="checkbox" name="photo_allowed" value="1"<?= (int)$project['photo_allowed'] === 1 ? ' checked' : '' ?>>
          <span><strong>Fotografieren erlaubt</strong><small class="field-hint">Nur setzen, wenn Projekt/Legitimationsschreiben Fotoaufnahmen ausdrücklich freigibt.</small></span>
        </label>
        <label>Verify-Scope
          <select name="scope_key">
            <option value="">Kein Scope</option>
            <option value="vodafone_skopos_2026"<?= $project['scope_key'] === 'vodafone_skopos_2026' ? ' selected' : '' ?>>Vodafone / SKOPOS NEXT 2026</option>
            <option value="hp_bare_retail_2025_2026"<?= $project['scope_key'] === 'hp_bare_retail_2025_2026' ? ' selected' : '' ?>>HP / BARE Retail 2025/2026</option>
          </select>
        </label>
        <label>Status
          <select name="is_active">
            <option value="1"<?= (int)$project['is_active'] === 1 ? ' selected' : '' ?>>Aktiv · für neue Ausweise auswählbar</option>
            <option value="0"<?= (int)$project['is_active'] !== 1 ? ' selected' : '' ?>>Inaktiv · nicht mehr auswählbar</option>
          </select>
        </label>
      </div>

      <div class="elite-profile-actions"><button type="submit">Projektstammdaten speichern</button></div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
