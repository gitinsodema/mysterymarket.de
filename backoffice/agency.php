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

function mmAgencyAdminFetch(int $id): ?array
{
    $stmt = mmDb()->prepare(
        'SELECT *
         FROM agencies
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id'=>$id]);
    return $stmt->fetch() ?: null;
}

$row = mmAgencyAdminFetch($id);
if (!$row) {
    http_response_code(404);
    exit('Not found');
}

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $action = (string)($_POST['action'] ?? 'save');

        try {
            if ($action === 'logo_upload') {
                $filename = mmAgencyStoreLogoUpload($_FILES['logo_file'] ?? [], $id);
                $stmt = mmDb()->prepare(
                    'UPDATE agencies
                     SET logo_asset = :logo_asset,
                         logo_source_url = NULL,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->execute([
                    'logo_asset'=>$filename,
                    'id'=>$id,
                ]);

                $drafts = mmDb()->prepare(
                    'UPDATE audit_verifications
                     SET agency_logo_asset = :logo_asset, updated_at = NOW()
                     WHERE agency_id = :agency_id
                       AND is_personal_verification = 1
                       AND is_active = 0'
                );
                $drafts->execute(['logo_asset'=>$filename,'agency_id'=>$id]);

                mmBackofficeAudit(
                    (int)$user['id'],
                    'agency.logo_uploaded',
                    'agency',
                    $id,
                    ['logo_asset'=>$filename]
                );

                header('Location: /backoffice/agency.php?id=' . $id . '&logo_saved=1', true, 303);
                exit;
            }

            if ($action === 'logo_url') {
                $logoUrl = trim((string)($_POST['logo_url'] ?? ''));
                $filename = mmAgencyStoreLogoFromUrl($logoUrl, $id);

                $stmt = mmDb()->prepare(
                    'UPDATE agencies
                     SET logo_asset = :logo_asset,
                         logo_source_url = :logo_source_url,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $stmt->execute([
                    'logo_asset'=>$filename,
                    'logo_source_url'=>$logoUrl,
                    'id'=>$id,
                ]);

                $drafts = mmDb()->prepare(
                    'UPDATE audit_verifications
                     SET agency_logo_asset = :logo_asset, updated_at = NOW()
                     WHERE agency_id = :agency_id
                       AND is_personal_verification = 1
                       AND is_active = 0'
                );
                $drafts->execute(['logo_asset'=>$filename,'agency_id'=>$id]);

                mmBackofficeAudit(
                    (int)$user['id'],
                    'agency.logo_imported',
                    'agency',
                    $id,
                    ['logo_source_url'=>$logoUrl,'logo_asset'=>$filename]
                );

                header('Location: /backoffice/agency.php?id=' . $id . '&logo_saved=1', true, 303);
                exit;
            }

            if ($action !== 'save') {
                throw new InvalidArgumentException('Unbekannte Aktion.');
            }

            $name = trim((string)($_POST['name'] ?? ''));
            $short = trim((string)($_POST['short_name'] ?? ''));
            $website = trim((string)($_POST['website_url'] ?? ''));
            $address1 = trim((string)($_POST['address_line1'] ?? ''));
            $address2 = trim((string)($_POST['address_line2'] ?? ''));
            $postal = trim((string)($_POST['postal_code'] ?? ''));
            $city = trim((string)($_POST['city'] ?? ''));
            $country = strtoupper(trim((string)($_POST['country_code'] ?? '')));
            $contactName = trim((string)($_POST['contact_name'] ?? ''));
            $contactEmail = strtolower(trim((string)($_POST['contact_email'] ?? '')));
            $contactPhone = trim((string)($_POST['contact_phone'] ?? ''));
            $publicNote = trim((string)($_POST['public_note'] ?? ''));
            $active = ($_POST['is_active'] ?? '') === '1';
            $eliteVisible = ($_POST['elite_visible'] ?? '') === '1';

            if ($name === '') {
                throw new InvalidArgumentException('Name ist erforderlich.');
            }
            if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Website ist ungültig.');
            }
            if ($contactEmail !== '' && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('E-Mail des Ansprechpartners ist ungültig.');
            }
            if ($country !== '' && !preg_match('/^[A-Z]{2}$/', $country)) {
                throw new InvalidArgumentException('Ländercode bitte zweistellig angeben, z. B. DE.');
            }

            $stmt = mmDb()->prepare(
                'UPDATE agencies
                 SET name = :name,
                     short_name = :short_name,
                     website_url = :website_url,
                     address_line1 = :address_line1,
                     address_line2 = :address_line2,
                     postal_code = :postal_code,
                     city = :city,
                     country_code = :country_code,
                     contact_name = :contact_name,
                     contact_email = :contact_email,
                     contact_phone = :contact_phone,
                     public_note = :public_note,
                     is_active = :is_active,
                     elite_visible = :elite_visible,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'name'=>$name,
                'short_name'=>$short !== '' ? $short : null,
                'website_url'=>$website !== '' ? $website : null,
                'address_line1'=>$address1 !== '' ? $address1 : null,
                'address_line2'=>$address2 !== '' ? $address2 : null,
                'postal_code'=>$postal !== '' ? $postal : null,
                'city'=>$city !== '' ? $city : null,
                'country_code'=>$country !== '' ? $country : null,
                'contact_name'=>$contactName !== '' ? $contactName : null,
                'contact_email'=>$contactEmail !== '' ? $contactEmail : null,
                'contact_phone'=>$contactPhone !== '' ? $contactPhone : null,
                'public_note'=>$publicNote !== '' ? $publicNote : null,
                'is_active'=>$active ? 1 : 0,
                'elite_visible'=>$eliteVisible ? 1 : 0,
                'id'=>$id,
            ]);

            mmBackofficeAudit(
                (int)$user['id'],
                'agency.updated',
                'agency',
                $id,
                ['is_active'=>$active,'elite_visible'=>$eliteVisible]
            );

            header('Location: /backoffice/agency.php?id=' . $id . '&updated=1', true, 303);
            exit;
        } catch (InvalidArgumentException|RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

$row = mmAgencyAdminFetch($id);

$projectsStmt = mmDb()->prepare(
    "SELECT id, project_name, is_active, photo_allowed, scope_key
     FROM credential_projects
     WHERE agency_id = :agency_id
     ORDER BY is_active DESC, project_name"
);
$projectsStmt->execute(['agency_id'=>$id]);
$projects = $projectsStmt->fetchAll();

mmHeader('Agentur', 'Agentur-Stammdaten verwalten.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Agenturen</p>
    <h1><?= mmEscape((string)$row['name']) ?></h1>
    <p class="lead">Zentraler Stammdatensatz für Kontakt, Projekte, Logo und spätere Elite-Shopper-Ansicht.</p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/agencies.php">Zurück zu Agenturen</a>
      <a class="button secondary" href="/backoffice/credential-project-new.php">Projekt anlegen</a>
    </div>
  </div>
</section>

<section class="section">
  <?php if (isset($_GET['updated'])): ?><div class="alert success"><strong>Agentur-Stammdaten gespeichert.</strong></div><?php endif; ?>
  <?php if (isset($_GET['logo_saved'])): ?><div class="alert success"><strong>Agenturlogo gespeichert.</strong> Neue Ausweise und Revisionen verwenden automatisch dieses Logo.</div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

  <div class="agency-master-card">
    <aside class="agency-master-identity">
      <div class="agency-master-logo">
        <?php if (!empty($row['logo_asset'])): ?>
          <img src="/backoffice/agency-asset.php?id=<?= $id ?>&type=logo" alt="<?= mmEscape((string)$row['name']) ?>">
        <?php else: ?>
          <div class="agency-logo-placeholder">Kein Logo</div>
        <?php endif; ?>
      </div>
      <div>
        <h2><?= mmEscape((string)$row['name']) ?></h2>
        <?php if (!empty($row['short_name'])): ?><p><?= mmEscape((string)$row['short_name']) ?></p><?php endif; ?>
        <p><?= mmBackofficeStatusBadge((int)$row['is_active'] === 1 ? 'active' : 'inactive') ?></p>
        <p><?= mmBackofficeStatusBadge((int)$row['elite_visible'] === 1 ? 'active' : 'pending', (int)$row['elite_visible'] === 1 ? 'für Elite sichtbar' : 'nur Admin') ?></p>
      </div>
    </aside>

    <div class="agency-master-facts">
      <div><small>Website</small><strong><?= !empty($row['website_url']) ? mmEscape((string)$row['website_url']) : '—' ?></strong></div>
      <div><small>Ansprechpartner</small><strong><?= !empty($row['contact_name']) ? mmEscape((string)$row['contact_name']) : '—' ?></strong></div>
      <div><small>E-Mail</small><strong><?= !empty($row['contact_email']) ? mmEscape((string)$row['contact_email']) : '—' ?></strong></div>
      <div><small>Telefon</small><strong><?= !empty($row['contact_phone']) ? mmEscape((string)$row['contact_phone']) : '—' ?></strong></div>
      <div class="wide"><small>Adresse</small><strong><?= mmEscape(trim(implode(', ', array_filter([
          (string)($row['address_line1'] ?? ''),
          (string)($row['address_line2'] ?? ''),
          trim((string)($row['postal_code'] ?? '') . ' ' . (string)($row['city'] ?? '')),
          (string)($row['country_code'] ?? ''),
      ]))) ?: '—') ?></strong></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <div class="form-card">
      <h2>Agenturlogo.</h2>
      <p class="partner-note">Das Logo ist zentral. Neue Ausweise und Revisionen übernehmen es automatisch; aktive Ausweise bleiben historische Snapshots.</p>

      <form method="post" action="/backoffice/agency.php?id=<?= $id ?>" enctype="multipart/form-data" class="agency-logo-form">
        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="logo_upload">
        <label>Logo hochladen
          <input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp" required>
          <small class="field-hint">PNG, JPG oder WebP bis 5 MB.</small>
        </label>
        <button type="submit">Logo hochladen</button>
      </form>

      <div class="agency-logo-or">oder</div>

      <form method="post" action="/backoffice/agency.php?id=<?= $id ?>" class="agency-logo-form">
        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="action" value="logo_url">
        <label>Logo aus URL importieren
          <input type="url" name="logo_url" required maxlength="1000" value="<?= mmEscape((string)($row['logo_source_url'] ?? '')) ?>" placeholder="https://…/logo.png">
          <small class="field-hint">Direkte Bild-URL. Das Bild wird geprüft und in unseren privaten Speicher kopiert.</small>
        </label>
        <button type="submit">Logo aus URL übernehmen</button>
      </form>
    </div>

    <div class="form-card">
      <h2>Projekte.</h2>
      <?php if (!$projects): ?>
        <div class="notice">Noch keine Projekte für diese Agentur.</div>
      <?php else: ?>
        <div class="agency-project-list">
          <?php foreach ($projects as $project): ?>
            <a href="/backoffice/credential-project.php?id=<?= (int)$project['id'] ?>">
              <div><strong><?= mmEscape((string)$project['project_name']) ?></strong><small><?= mmEscape((string)($project['scope_key'] ?: 'kein Scope')) ?></small></div>
              <?= mmBackofficeStatusBadge((int)$project['is_active'] === 1 ? 'active' : 'inactive') ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <h2>Stammdaten.</h2>
    <form method="post" action="/backoffice/agency.php?id=<?= $id ?>">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= $id ?>">
      <input type="hidden" name="action" value="save">

      <div class="form-grid">
        <label>Name<input name="name" maxlength="200" required value="<?= mmEscape((string)$row['name']) ?>"></label>
        <label>Kurzname<input name="short_name" maxlength="120" value="<?= mmEscape((string)($row['short_name'] ?? '')) ?>"></label>
        <label class="wide">Website<input type="url" name="website_url" maxlength="500" value="<?= mmEscape((string)($row['website_url'] ?? '')) ?>"></label>

        <label class="wide">Adresse<input name="address_line1" maxlength="200" value="<?= mmEscape((string)($row['address_line1'] ?? '')) ?>"></label>
        <label class="wide">Adresszusatz<input name="address_line2" maxlength="200" value="<?= mmEscape((string)($row['address_line2'] ?? '')) ?>"></label>
        <label>PLZ<input name="postal_code" maxlength="24" value="<?= mmEscape((string)($row['postal_code'] ?? '')) ?>"></label>
        <label>Ort<input name="city" maxlength="120" value="<?= mmEscape((string)($row['city'] ?? '')) ?>"></label>
        <label>Land<input name="country_code" maxlength="2" value="<?= mmEscape((string)($row['country_code'] ?? '')) ?>" placeholder="DE"></label>

        <label>Ansprechpartner<input name="contact_name" maxlength="160" value="<?= mmEscape((string)($row['contact_name'] ?? '')) ?>"></label>
        <label>E-Mail<input type="email" name="contact_email" maxlength="254" value="<?= mmEscape((string)($row['contact_email'] ?? '')) ?>"></label>
        <label>Telefon<input name="contact_phone" maxlength="60" value="<?= mmEscape((string)($row['contact_phone'] ?? '')) ?>"></label>

        <label class="wide">Hinweis für Elite Shopper<textarea name="public_note"><?= mmEscape((string)($row['public_note'] ?? '')) ?></textarea></label>
        <label class="privacy-check"><input type="checkbox" name="is_active" value="1"<?= (int)$row['is_active'] === 1 ? ' checked' : '' ?>> Aktiv</label>
        <label class="privacy-check"><input type="checkbox" name="elite_visible" value="1"<?= (int)$row['elite_visible'] === 1 ? ' checked' : '' ?>> Später für Elite Shopper sichtbar</label>
      </div>

      <div class="elite-profile-actions"><button type="submit">Agentur-Stammdaten speichern</button></div>
    </form>
  </div>
</section>
<?php mmFooter(); ?>
