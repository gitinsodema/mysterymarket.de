<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('elite');
$error = '';
$success = '';

$stmt = mmDb()->prepare(
    'SELECT m.*, u.email
     FROM elite_members m
     JOIN backoffice_users u ON u.id = m.user_id
     WHERE m.user_id = :user_id
     LIMIT 1'
);
$stmt->execute(['user_id'=>(int)$user['id']]);
$member = $stmt->fetch();
if (!$member) {
    http_response_code(404);
    exit('Member profile not found');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $action = (string)($_POST['action'] ?? 'profile');

        if ($action === 'profile') {
            $displayName = trim((string)($_POST['display_name'] ?? ''));
            $organisation = trim((string)($_POST['organisation'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $streetName = trim((string)($_POST['street_name'] ?? ''));
            $houseNumber = trim((string)($_POST['house_number'] ?? ''));
            $address1 = trim($streetName . ($streetName !== '' && $houseNumber !== '' ? ' ' : '') . $houseNumber);
            if ($address1 === '') {
                $address1 = trim((string)($_POST['address_line1_fallback'] ?? ''));
            }
            $address2 = trim((string)($_POST['address_line2'] ?? ''));
            $postal = trim((string)($_POST['postal_code'] ?? ''));
            $city = trim((string)($_POST['city_manual'] ?? ''));
            if ($city === '') {
                $city = trim((string)($_POST['city'] ?? ''));
            }
            $country = strtoupper(trim((string)($_POST['country_code'] ?? '')));
            $adminAtlasId = trim((string)($_POST['administrative_unit_atlas_id'] ?? ''));
            $adminName = trim((string)($_POST['administrative_unit_name'] ?? ''));
            $postalAtlasId = trim((string)($_POST['postal_area_atlas_id'] ?? ''));
            $localityAtlasId = trim((string)($_POST['locality_atlas_id'] ?? ''));
            $localityName = trim((string)($_POST['locality_name'] ?? $city));
            $streetAtlasId = trim((string)($_POST['street_atlas_id'] ?? ''));
            if ($streetName !== trim((string)($member['street_name'] ?? ''))) {
                $streetAtlasId = '';
            }
            if (trim((string)($_POST['city_manual'] ?? '')) !== '') {
                $localityAtlasId = '';
            }
            $regions = trim((string)($_POST['preferred_regions'] ?? ''));
            $mobility = trim((string)($_POST['mobility_profile'] ?? ''));
            $shopperMatch = trim((string)($_POST['shoppermatch_profile_url'] ?? ''));

            if ($displayName === '') {
                $error = 'Name ist erforderlich.';
            } elseif ($country !== '' && !preg_match('/^[A-Z]{2}$/', $country)) {
                $error = 'Ländercode bitte zweistellig angeben, z. B. DE.';
            } elseif ($shopperMatch !== '' && !filter_var($shopperMatch, FILTER_VALIDATE_URL)) {
                $error = 'Der ShopperMatch-Profillink ist ungültig.';
            } else {
                $update = mmDb()->prepare(
                    'UPDATE elite_members
                     SET display_name = :display_name,
                         organisation = :organisation,
                         phone = :phone,
                         address_line1 = :address1,
                         address_line2 = :address2,
                         postal_code = :postal,
                         city = :city,
                         country_code = :country,
                         administrative_unit_atlas_id = :admin_atlas_id,
                         administrative_unit_name = :admin_name,
                         postal_area_atlas_id = :postal_atlas_id,
                         locality_atlas_id = :locality_atlas_id,
                         locality_name = :locality_name,
                         street_atlas_id = :street_atlas_id,
                         street_name = :street_name,
                         house_number = :house_number,
                         preferred_regions = :regions,
                         mobility_profile = :mobility,
                         shoppermatch_profile_url = :shoppermatch,
                         updated_at = NOW()
                     WHERE id = :id'
                );
                $update->execute([
                    'display_name'=>$displayName,
                    'organisation'=>$organisation !== '' ? $organisation : null,
                    'phone'=>$phone !== '' ? $phone : null,
                    'address1'=>$address1 !== '' ? $address1 : null,
                    'address2'=>$address2 !== '' ? $address2 : null,
                    'postal'=>$postal !== '' ? $postal : null,
                    'city'=>$city !== '' ? $city : null,
                    'country'=>$country !== '' ? $country : null,
                    'admin_atlas_id'=>$adminAtlasId !== '' ? $adminAtlasId : null,
                    'admin_name'=>$adminName !== '' ? $adminName : null,
                    'postal_atlas_id'=>$postalAtlasId !== '' ? $postalAtlasId : null,
                    'locality_atlas_id'=>$localityAtlasId !== '' ? $localityAtlasId : null,
                    'locality_name'=>$localityName !== '' ? $localityName : null,
                    'street_atlas_id'=>$streetAtlasId !== '' ? $streetAtlasId : null,
                    'street_name'=>$streetName !== '' ? $streetName : null,
                    'house_number'=>$houseNumber !== '' ? $houseNumber : null,
                    'regions'=>$regions !== '' ? $regions : null,
                    'mobility'=>$mobility !== '' ? $mobility : null,
                    'shoppermatch'=>$shopperMatch !== '' ? $shopperMatch : null,
                    'id'=>(int)$member['id'],
                ]);
                mmBackofficeAudit((int)$user['id'], 'elite_profile.updated', 'elite_member', (int)$member['id']);
                header('Location: /backoffice/profile.php?saved=1', true, 303);
                exit;
            }
        } elseif ($action === 'membership_request') {
            $requestType = (string)($_POST['request_type'] ?? '');
            $note = trim((string)($_POST['note'] ?? ''));
            if (!in_array($requestType, ['pause','end'], true)) {
                $error = 'Ungültige Anfrage.';
            } else {
                $openCheck = mmDb()->prepare(
                    'SELECT COUNT(*) FROM elite_membership_requests
                     WHERE member_id = :member_id AND request_status = \'open\''
                );
                $openCheck->execute(['member_id'=>(int)$member['id']]);
                if ((int)$openCheck->fetchColumn() > 0) {
                    $error = 'Es gibt bereits eine offene Mitgliedschaftsanfrage.';
                } else {
                    $insert = mmDb()->prepare(
                        'INSERT INTO elite_membership_requests
                         (member_id, request_type, request_status, note, created_at)
                         VALUES (:member_id, :request_type, \'open\', :note, NOW())'
                    );
                    $insert->execute([
                        'member_id'=>(int)$member['id'],
                        'request_type'=>$requestType,
                        'note'=>$note !== '' ? $note : null,
                    ]);
                    $requestId = (int)mmDb()->lastInsertId();
                    mmBackofficeAudit((int)$user['id'], 'elite_membership_request.created', 'elite_membership_request', $requestId, ['type'=>$requestType]);
                    header('Location: /backoffice/profile.php?requested=1', true, 303);
                    exit;
                }
            }
        }
    }
}

$stmt->execute(['user_id'=>(int)$user['id']]);
$member = $stmt->fetch();

$requestStmt = mmDb()->prepare(
    'SELECT id, request_type, request_status, note, created_at, resolved_at
     FROM elite_membership_requests
     WHERE member_id = :member_id
     ORDER BY created_at DESC
     LIMIT 10'
);
$requestStmt->execute(['member_id'=>(int)$member['id']]);
$requests = $requestStmt->fetchAll();

mmHeader('Mein Elite Profil', 'Geschütztes Elite-Shopper-Profil.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Elite Shopper · <?= mmEscape((string)$member['member_code']) ?></p>
    <h1>Mein Profil.</h1>
    <p class="lead">Mitgliedschaft, Einsatzprofil und Kontaktdaten verwalten.</p>
    <div class="actions"><a class="button secondary" href="/backoffice/">Dashboard</a></div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="card">
      <span class="badge">Mitgliedschaft</span>
      <h2><?= mmEscape((string)$member['membership_status']) ?></h2>
      <p><strong>E-Mail:</strong> <?= mmEscape((string)$member['email']) ?></p>
      <p><strong>Mitglied seit:</strong> <?= mmEscape((string)($member['joined_at'] ?: '—')) ?></p>
    </article>
    <article class="card">
      <span class="badge">ShopperMatch</span>
      <h3>Eigenständige Job-/Matching-Plattform</h3>
      <p>MysteryMarket Elite und ShopperMatch bleiben getrennte Systeme.</p>
      <?php if (!empty($member['shoppermatch_profile_url'])): ?>
        <a href="<?= mmEscape((string)$member['shoppermatch_profile_url']) ?>" target="_blank" rel="noopener noreferrer">Profil öffnen →</a>
      <?php endif; ?>
    </article>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <?php if (isset($_GET['saved'])): ?><div class="alert success"><strong>Profil gespeichert.</strong></div><?php endif; ?>
    <?php if (isset($_GET['requested'])): ?><div class="alert success"><strong>Mitgliedschaftsanfrage gespeichert.</strong></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <h2>Profil & Einsatz</h2>
    <form method="post" action="/backoffice/profile.php" data-atlas-address-form>
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="action" value="profile">
      <input type="hidden" name="administrative_unit_atlas_id" data-atlas-admin-id value="<?= mmEscape((string)($member['administrative_unit_atlas_id'] ?? '')) ?>">
      <input type="hidden" name="administrative_unit_name" data-atlas-admin-name value="<?= mmEscape((string)($member['administrative_unit_name'] ?? '')) ?>">
      <input type="hidden" name="postal_area_atlas_id" data-atlas-postal-id value="<?= mmEscape((string)($member['postal_area_atlas_id'] ?? '')) ?>">
      <input type="hidden" name="locality_atlas_id" data-atlas-locality-id value="<?= mmEscape((string)($member['locality_atlas_id'] ?? '')) ?>">
      <input type="hidden" name="locality_name" data-atlas-locality-name value="<?= mmEscape((string)($member['locality_name'] ?? $member['city'] ?? '')) ?>">
      <input type="hidden" name="street_atlas_id" data-atlas-street-id value="<?= mmEscape((string)($member['street_atlas_id'] ?? '')) ?>">

      <label>Name<input name="display_name" maxlength="150" required value="<?= mmEscape((string)$member['display_name']) ?>"></label>
      <label>Organisation<input name="organisation" maxlength="200" value="<?= mmEscape((string)($member['organisation'] ?? '')) ?>"></label>
      <label>Telefon<input name="phone" maxlength="60" value="<?= mmEscape((string)($member['phone'] ?? '')) ?>"></label>
      <div class="form-grid">
        <label>Land
          <select name="country_code" data-atlas-country data-current-country="<?= mmEscape((string)($member['country_code'] ?? '')) ?>">
            <?php if (!empty($member['country_code'])): ?>
              <option value="<?= mmEscape((string)$member['country_code']) ?>" selected><?= mmEscape((string)$member['country_code']) ?> · aktueller Wert</option>
            <?php else: ?>
              <option value="">Land wird geladen …</option>
            <?php endif; ?>
          </select>
        </label>
        <label>PLZ
          <input name="postal_code" maxlength="24" data-atlas-postal value="<?= mmEscape((string)($member['postal_code'] ?? '')) ?>" autocomplete="postal-code">
          <small class="field-hint" data-atlas-postal-status>Nach Land + PLZ lädt ATLAS passende Orte.</small>
        </label>
        <label>Ort
          <select name="city" data-atlas-locality data-current-locality="<?= mmEscape((string)($member['locality_atlas_id'] ?? '')) ?>">
            <option value="<?= mmEscape((string)($member['city'] ?? '')) ?>"><?= mmEscape((string)($member['city'] ?? 'Bitte PLZ eingeben')) ?></option>
          </select>
          <small class="field-hint">Bei eindeutiger PLZ wird der Ort automatisch gewählt.</small>
        </label>
        <label>Region / Bundesland
          <select data-atlas-subdivision data-current-admin="<?= mmEscape((string)($member['administrative_unit_atlas_id'] ?? '')) ?>">
            <option value="">Wird nach Möglichkeit automatisch erkannt</option>
          </select>
        </label>
        <label>Straße
          <input name="street_name" maxlength="200" data-atlas-street list="atlas-street-options" autocomplete="address-line1"
                 value="<?= mmEscape((string)($member['street_name'] ?? '')) ?>"
                 placeholder="Straße eingeben">
          <datalist id="atlas-street-options" data-atlas-street-options></datalist>
          <small class="field-hint" data-atlas-street-status>ATLAS-Suche ab 2 Zeichen; Freitext bleibt möglich.</small>
        </label>
        <label>Hausnummer
          <input name="house_number" maxlength="40" data-atlas-house-number value="<?= mmEscape((string)($member['house_number'] ?? '')) ?>">
        </label>
        <div class="wide atlas-manual-toggle-wrap">
          <button type="button" class="button secondary" data-atlas-manual-toggle aria-expanded="<?= (!empty($member['address_line2']) || empty($member['street_name']) && !empty($member['address_line1'])) ? 'true' : 'false' ?>">
            Meine Adresse wird nicht erkannt
          </button>
        </div>
        <div class="wide atlas-manual-fields" data-atlas-manual-fields<?= (!empty($member['address_line2']) || empty($member['street_name']) && !empty($member['address_line1'])) ? '' : ' hidden' ?>>
          <div class="form-grid">
            <label>Ort manuell
              <input name="city_manual" maxlength="120" data-atlas-locality-manual placeholder="Nur verwenden, wenn der Ort in ATLAS fehlt">
              <small class="field-hint">Freitext-Fallback ohne erfundene ATLAS-ID.</small>
            </label>
            <label>Adresszusatz
              <input name="address_line2" maxlength="200" value="<?= mmEscape((string)($member['address_line2'] ?? '')) ?>">
            </label>
            <label class="wide">Straße / Adresse manuell
              <input name="address_line1_fallback" maxlength="200" placeholder="Nur falls keine strukturierte Straße verfügbar ist"
                     value="<?= empty($member['street_name']) ? mmEscape((string)($member['address_line1'] ?? '')) : '' ?>">
              <small class="field-hint">Fallback ohne erfundene ATLAS-ID.</small>
            </label>
            <label class="wide">Hinweis
              <small class="field-hint">Manuelle Werte werden gespeichert, aber niemals mit erfundenen ATLAS-IDs verknüpft.</small>
            </label>
          </div>
        </div>
        <label>Mobilitätsprofil
          <select name="mobility_profile">
            <?php
            $mobilityOptions = ['','Auto','ÖPNV','Bahn','Fahrrad','Zu Fuß','Flexibel / kombiniert'];
            foreach ($mobilityOptions as $option):
            ?>
              <option value="<?= mmEscape($option) ?>"<?= ($member['mobility_profile'] ?? '') === $option ? ' selected' : '' ?>><?= mmEscape($option === '' ? 'Noch nicht gewählt' : $option) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <label>Bevorzugte Regionen<textarea name="preferred_regions" placeholder="z. B. Düsseldorf, Ruhrgebiet, NRW"><?= mmEscape((string)($member['preferred_regions'] ?? '')) ?></textarea></label>
      <label>ShopperMatch-Profillink<input type="url" name="shoppermatch_profile_url" maxlength="500" value="<?= mmEscape((string)($member['shoppermatch_profile_url'] ?? '')) ?>"></label>
      <button type="submit">Profil speichern</button>
    </form>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <h2>Mitgliedschaft</h2>
    <p class="partner-note">Pause oder Beendigung wird als Anfrage an den Admin übergeben. Dein Account bleibt bis zur Bearbeitung unverändert.</p>
    <form method="post" action="/backoffice/profile.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="action" value="membership_request">
      <label>Aktion
        <select name="request_type">
          <option value="pause">Mitgliedschaft pausieren</option>
          <option value="end">Mitgliedschaft beenden</option>
        </select>
      </label>
      <label>Hinweis an Admin<textarea name="note"></textarea></label>
      <button type="submit" class="button secondary">Anfrage senden</button>
    </form>

    <?php if ($requests): ?>
      <div class="backoffice-request-history">
        <h3>Letzte Anfragen</h3>
        <?php foreach ($requests as $request): ?>
          <div><strong><?= mmEscape((string)$request['request_type']) ?></strong><span><?= mmEscape((string)$request['request_status']) ?></span><small><?= mmEscape((string)$request['created_at']) ?></small></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php mmFooter(); ?>
