<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');

$stmt = mmDb()->query(
    'SELECT m.id, m.member_code, m.display_name, m.membership_status, m.city, m.preferred_regions,
            u.email, u.account_status, u.last_login_at
     FROM elite_members m
     JOIN backoffice_users u ON u.id = m.user_id
     ORDER BY m.created_at DESC'
);
$members = $stmt->fetchAll();

mmHeader('Elite Shopper verwalten', 'Interne Elite-Shopper-Verwaltung.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · Elite Shopper</p>
    <h1>Mitglieder.</h1>
    <p class="lead">Status, Regionen und Zugangsstand der Elite Shopper.</p>
    <div class="actions">
      <a class="button" href="/backoffice/member-new.php">Mitglied anlegen</a>
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>
<section class="section">
  <?php if (!$members): ?>
    <div class="notice"><strong>Noch keine Elite Shopper angelegt.</strong><p>Der erste Datensatz kann jetzt als Einladung angelegt werden.</p></div>
  <?php else: ?>
    <div class="backoffice-table-wrap">
      <table class="backoffice-table">
        <thead><tr><th>ID</th><th>Name</th><th>E-Mail</th><th>Mitgliedschaft</th><th>Login</th><th>Region</th><th>Letzter Login</th></tr></thead>
        <tbody>
        <?php foreach ($members as $member): ?>
          <tr>
            <td><strong><?= mmEscape((string)$member['member_code']) ?></strong></td>
            <td><?= mmEscape((string)$member['display_name']) ?></td>
            <td><?= mmEscape((string)$member['email']) ?></td>
            <td><span class="status"><?= mmEscape((string)$member['membership_status']) ?></span></td>
            <td><?= mmEscape((string)$member['account_status']) ?></td>
            <td><?= mmEscape((string)($member['preferred_regions'] ?: $member['city'] ?: '—')) ?></td>
            <td><?= mmEscape((string)($member['last_login_at'] ?: '—')) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php mmFooter(); ?>
