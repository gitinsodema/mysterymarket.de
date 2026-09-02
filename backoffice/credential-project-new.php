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
    'project_name'=>'',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach (array_keys($values) as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
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

            if ($values['project_name'] === '' || mb_strlen($values['project_name']) > 200) {
                throw new InvalidArgumentException('Projektname ist erforderlich.');
            }

            $stmt = mmDb()->prepare(
                "INSERT INTO credential_projects
                 (agency_id, customer_name, project_name,
                  authorization_document_asset, authorization_document_label,
                  scope_key, photo_allowed, is_active, created_at, updated_at)
                 VALUES (:agency_id, :project_name, :project_name,
                         :document, 'Offizielles Legitimationsschreiben',
                         NULL, 0, 0, NOW(), NOW())"
            );
            $document = mmCredentialStoreProjectDocument(
                $_FILES['authorization_document'] ?? [],
                'project_admin_new'
            );

            $stmt->execute([
                'agency_id'=>$agencyId,
                'project_name'=>$values['project_name'],
                'document'=>$document,
            ]);
            $id = (int)mmDb()->lastInsertId();

            mmBackofficeAudit(
                (int)$user['id'],
                'credential_project.created',
                'credential_project',
                $id,
                [
                    'agency_id'=>$agencyId,
                    'project_name'=>$values['project_name'],
                ]
            );

            header('Location: /backoffice/credential-project.php?id=' . $id . '&created=1', true, 303);
            exit;
        } catch (InvalidArgumentException|RuntimeException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'Dieses Projekt existiert für die Agentur bereits.'
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
    <p class="lead">Agentur, Projekt und Legitimationsschreiben anlegen. Das Projekt bleibt zunächst inaktiv, bis Logo, Scope und Fotoerlaubnis geprüft sind.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/credential-projects.php">Zurück zu Projekten</a></div>
  </div>
</section>

<section class="section">
  <div class="form-card compact-admin-form">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <form method="post" action="/backoffice/credential-project-new.php" enctype="multipart/form-data">
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
        <label class="wide">Projekt
          <input name="project_name" maxlength="200" required value="<?= mmEscape($values['project_name']) ?>" placeholder="z. B. ALDO">
        </label>
        <label class="wide">Legitimationsschreiben
          <input type="file" name="authorization_document" accept="application/pdf" required>
          <small class="field-hint">PDF bis 10 MB. Auch bei direkter Admin-Anlage ist das Legitimationsschreiben Pflicht.</small>
        </label>

      </div>
      <div class="elite-profile-actions"><button type="submit">Projektstamm anlegen</button></div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
