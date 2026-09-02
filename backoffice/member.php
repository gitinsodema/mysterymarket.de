<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit('Not found');
}

$statuses = ['invited','pending_review','active','paused','suspended','ended'];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        $error = 'Ungültige Sitzung.';
    } else {
        $status = (string)($_POST['membership_status'] ?? '');
        if (!in_array($status, $statuses, true)) {
            $error = 'Ungültiger Mitgliedsstatus.';
        } else {
            $pdo = mmDb();
            $stmt = $pdo->prepare(
                'UPDATE elite_members
                 SET membership_status = :status,
                     joined_at = CASE WHEN :status = \'active\' AND joined_at IS NULL THEN NOW() ELSE joined_at END,
                     paused_at = CASE WHEN :status = \'paused\' THEN NOW() ELSE paused_at END,
                     ended_at = CASE WHEN :status = \'ended\' THEN NOW() ELSE ended_at END,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute(['status'=>$status,'id'=>$id]);

            $accountStatus = $status === 'active' ? 'active' : 'disabled';
            $account = $pdo->prepare(
                'UPDATE backoffice_users u
                 JOIN elite_members m ON m.user_id = u.id
                 SET u.account_status = :account_status, u.updated_at = NOW()
                 WHERE m.id = :id'
            );
            $account->execute(['account_status'=>$accountStatus,'id'=>$id]);

            mmBackofficeAudit((int)$user['id'], 'elite_member.status_changed', 'elite_member', $id, ['status'=>$status]);
            header('Location: /backoffice/member.php?id=' . $id . '&updated=1', true, 303);
            exit;
        }
    }
}

$stmt = mmDb()->prepare(
    'SELECT m.*, u.email, u.account_status, u.last_login_at
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

mmHeader('Elite Shopper', 'Interne Mitgliedsverwaltung.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · <?= mmEscape((string)$member['member_code']) ?></p>
    <h1><?= mmEscape((string)$member['display_name']) ?></h1>
    <p class="lead"><?= mmEscape((string)$member['email']) ?></p>
    <div class="actions">
      <?php if (in_array($member['membership_status'], ['invited','pending_review'], true)): ?>
        <a class="button" href="/backoffice/invite.php?id=<?= (int)$member['id'] ?>">Aktivierungslink</a>
      <?php endif; ?>
      <a class="button secondary" href="/backoffice/members.php">Zurück zur Liste</a>
    </div>
  </div>
</section>
<section class="section">
  <div class="grid three">
    <article class="card">
      <span class="badge">Mitgliedschaft</span>
      <h2><?= mmBackofficeStatusBadge((string)$member['membership_status']) ?></h2>
      <p>Login: <?= mmBackofficeStatusBadge((string)$member['account_status']) ?></p>
      <p>Letzter Login: <?= mmEscape((string)($member['last_login_at'] ?: '—')) ?></p>
    </article>
    <article class="card">
      <span class="badge">Ausweisfoto</span>
      <?php if (!empty($member['profile_photo_asset'])): ?>
        <div class="admin-member-photo"><img src="/backoffice/member-photo.php?member_id=<?= (int)$member['id'] ?>" alt=""></div>
        <p><?= mmBackofficeStatusBadge('active', 'Profilfoto vorhanden') ?></p>
      <?php else: ?>
        <p><?= mmBackofficeStatusBadge('pending', 'Profilfoto fehlt') ?></p>
      <?php endif; ?>
    </article>
    <article class="card">
      <span class="badge">Einsatzprofil</span>
      <h3><?= mmEscape((string)($member['city'] ?: 'Ort noch offen')) ?></h3>
      <p><?= mmEscape((string)($member['preferred_regions'] ?: 'Regionen noch nicht gepflegt')) ?></p>
      <p><?= mmEscape((string)($member['mobility_profile'] ?: 'Mobilitätsprofil noch nicht gepflegt')) ?></p>
    </article>
  </div>
</section>
<section class="section">
  <div class="form-card">
    <?php if ($error !== ''): ?><div class="alert"><?= mmEscape($error) ?></div><?php endif; ?>
    <h2>Status ändern</h2>
    <form method="post" action="/backoffice/member.php">
      <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
      <input type="hidden" name="id" value="<?= (int)$member['id'] ?>">
      <label>Mitgliedsstatus
        <select name="membership_status">
          <?php foreach ($statuses as $status): ?>
            <option value="<?= mmEscape($status) ?>"<?= $member['membership_status'] === $status ? ' selected' : '' ?>><?= mmEscape($status) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button type="submit">Status speichern</button>
    </form>
    <p class="partner-note">Ein aktiver Mitgliedsstatus schaltet den Account grundsätzlich frei. Eingeladene Mitglieder aktivieren ihren Zugang über den einmaligen Aktivierungslink und setzen dabei ihr persönliches Passwort.</p>
  </div>
</section>
<?php mmFooter(); ?>
