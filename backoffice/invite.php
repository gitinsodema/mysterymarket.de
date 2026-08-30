<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit('Not found');
}

$stmt = mmDb()->prepare(
    'SELECT m.id, m.user_id, m.member_code, m.display_name, m.membership_status,
            u.email, u.account_status
     FROM elite_members m
     JOIN backoffice_users u ON u.id = m.user_id
     WHERE m.id = :id
     LIMIT 1'
);
$stmt->execute(['id'=>$id]);
$member = $stmt->fetch();
if (!$member) {
    http_response_code(404);
    exit('Not found');
}

$error = '';
$activationUrl = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } elseif (!in_array(($member['membership_status'] ?? ''), ['invited','pending_review'], true)) {
        $error = 'Ein Aktivierungslink kann nur für eingeladene oder noch zu prüfende Mitglieder erzeugt werden.';
    } else {
        try {
            $token = mmBackofficeCreateActivationToken((int)$member['user_id'], (int)$user['id']);
            $activationUrl = 'https://mysterymarket.de/backoffice/activate.php?token=' . rawurlencode($token);
        } catch (Throwable $e) {
            $error = 'Aktivierungslink konnte nicht erzeugt werden.';
        }
    }
}

mmHeader('Elite Einladung', 'Elite-Shopper-Aktivierungslink erzeugen.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Einladung</p>
    <h1><?= mmEscape((string)$member['display_name']) ?></h1>
    <p class="lead"><?= mmEscape((string)$member['email']) ?> · <?= mmEscape((string)$member['member_code']) ?></p>
    <div class="actions">
      <a class="button secondary" href="/backoffice/member.php?id=<?= (int)$member['id'] ?>">Zurück zum Mitglied</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="form-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>

    <?php if ($activationUrl !== ''): ?>
      <div class="alert success">
        <strong>Aktivierungslink erstellt.</strong>
        <p>Der Link ist 48 Stunden gültig und wird nur einmal angezeigt. Ein neuer Link macht ältere unbenutzte Links ungültig.</p>
      </div>
      <label>Aktivierungslink
        <textarea class="backoffice-token-output" readonly><?= mmEscape($activationUrl) ?></textarea>
      </label>
      <p class="partner-note">Diesen Link manuell und vertraulich an den Elite Shopper senden. MysteryMarket verschickt in R1.1 noch keine Einladung automatisch.</p>
    <?php else: ?>
      <h2>Aktivierungslink erzeugen</h2>
      <p>Der Shopper setzt über diesen einmaligen Link selbst ein Passwort. Der Link ist 48 Stunden gültig.</p>
      <form method="post" action="/backoffice/invite.php">
        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
        <input type="hidden" name="id" value="<?= (int)$member['id'] ?>">
        <button type="submit">Neuen Aktivierungslink erzeugen</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php mmFooter(); ?>
