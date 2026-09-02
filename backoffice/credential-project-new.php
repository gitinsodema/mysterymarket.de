<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$error = '';

$agencies = mmDb()->query(
    "SELECT id, name
     FROM agencies
     WHERE is_active = 1
     ORDER BY name"
)->fetchAll();

$values = [
    'agency_id'=>'',
    'customer_name'=>'',
    'project_name'=>'',
    'scope_key'=>'',
];
$photoAllowed = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (array_keys($values) as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $photoAllowed = ($_POST['photo_allowed'] ?? '') === '1';

    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        try {
            $agencyId = (int)$values['agency_id'];
            if ($agencyId < 1) {
                throw new InvalidArgumentException('Agentur ist erforderlich.');
            }
            $agencyStmt = mmDb()->prepare('SELECT id FROM agencies WHERE id = :id AND is_active = 1 LIMIT 1');
            $agencyStmt->execute(['id'=>$agencyId]);
            if (!$agencyStmt->fetchColumn()) {
                throw new InvalidArgumentException('Die Agentur ist nicht verfügbar.');
            }

            if ($values['customer_name'] === '' || mb_strlen($values['customer_name']) > 200) {
                throw new InvalidArgumentException('Projektkunde ist erforderlich.');
            }
            if ($values['project_name'] === '' || mb_strlen($values['project_name']) > 200) {
                throw new InvalidArgumentException('Projektname ist erforderlich.');
            }

            $allowedScopes = ['', 'vodafone_skopos_2026', 'hp_bare_retail_2025_2026'];
            if (!in_array($values['scope_key'], $allowedScopes, true)) {
                throw new InvalidArgumentException('Ungültiger Verify-Scope.');
            }

            $stmt = mmDb()->prepare(
                'INSERT INTO credential_projects
                 (agency_id, customer_name, project_name, scope_key, photo_allowed, is_active, created_at, updated_at)
                 VALUES (:agency_id, :customer_name, :project_name, :scope_key, :photo_allowed, 1, NOW(), NOW())'
            );
            $stmt->execute([
                'agency_id'=>$agencyId,
                'customer_name'=>$values['customer_name'],
                'project_name'=>$values['project_name'],
                'scope_key'=>$values['scope_key'] !== '' ? $values['scope_key'] : null,
                'photo_allowed'=>$photoAllowed ? 1 : 0,
            ]);
            $id = (int)mmDb()->lastInsertId();

            mmBackofficeAudit(
                (int)$user['id'],
                'credential_project.created',
                'credential_project',
                $id,
                [
                    'agency_id'=>$agencyId,
                    'customer_name'=>$values['customer_name'],
                    'project_name'=>$values['project_name'],
                    'scope_key'=>$values['scope_key'] !== '' ? $values['scope_key'] : null,
                    'photo_allowed'=>$photoAllowed,
                ]
            );

            header('Location: /backoffice/credential-projects.php?created=1', true, 303);
            exit;
        } catch (InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'Diese Agentur-/Projektkunde-/Projekt-Kombination existiert bereits.'
                : 'Projekt konnte nicht angelegt werden.';
        }
    }
}

mmHeader('Ausweis-Projekt anlegen', 'Kontrolliertes Projekt für Verify-Ausweise anlegen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Ausweis-Stammdaten</p>
    <h1>Projekt anlegen.</h1>
    <p class="lead">Diese Stammdaten bestimmen später direkt die auswählbaren Angaben auf Verify-Ausweisen.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/credential-projects.php">Zurück zu Projekten</a></div>
  </div>
</section>

<section class="section">
  <div class="form-card compact-admin-form">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/credential-project-new.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <div class="form-grid">
        <label>Agentur
          <select name="agency_id" required>
            <option value="">Bitte auswählen</option>
            <?php foreach ($agencies as $agency): ?>
              <option value="<?= (int)$agency['id'] ?>"<?= (int)$values['agency_id'] === (int)$agency['id'] ? ' selected' : '' ?>><?= mmEscape((string)$agency['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Projektkunde
          <input name="customer_name" maxlength="200" required value="<?= mmEscape($values['customer_name']) ?>">
        </label>
        <label class="wide">Projekt
          <input name="project_name" maxlength="200" required value="<?= mmEscape($values['project_name']) ?>">
        </label>
        <label class="wide credential-photo-permission"><input type="checkbox" name="photo_allowed" value="1"<?= $photoAllowed ? ' checked' : '' ?>> <span><strong>Fotografieren erlaubt</strong><small class="field-hint">Nur aktivieren, wenn Projekt/Legitimationsschreiben Fotoaufnahmen ausdrücklich freigibt.</small></span></label>
        <label class="wide">Verify-Scope
          <select name="scope_key">
            <option value="">Kein Scope</option>
            <option value="vodafone_skopos_2026"<?= $values['scope_key'] === 'vodafone_skopos_2026' ? ' selected' : '' ?>>Vodafone / SKOPOS NEXT 2026</option>
            <option value="hp_bare_retail_2025_2026"<?= $values['scope_key'] === 'hp_bare_retail_2025_2026' ? ' selected' : '' ?>>HP / BARE Retail 2025/2026</option>
          </select>
        </label>
      </div>
      <div class="elite-profile-actions"><button type="submit">Projekt freigeben</button></div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
