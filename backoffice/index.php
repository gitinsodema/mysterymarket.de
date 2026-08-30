<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$role = (string)$user['role'];

$dashboard = [
    'active_members' => 0,
    'feed_posts' => 0,
    'new_contacts' => 0,
    'open_approvals' => 0,
    'membership_requests' => 0,
];

if ($role === 'admin') {
    $dashboard['active_members'] = (int)mmDb()->query("SELECT COUNT(*) FROM elite_members WHERE membership_status = 'active'")->fetchColumn();
    $dashboard['feed_posts'] = (int)mmDb()->query("SELECT COUNT(*) FROM elite_feed_posts WHERE is_active = 1")->fetchColumn();
    $dashboard['new_contacts'] = (int)mmDb()->query("SELECT COUNT(*) FROM contact_requests WHERE status = 'new'")->fetchColumn();
    $dashboard['open_approvals'] = (int)mmDb()->query("SELECT COUNT(*) FROM agency_approvals WHERE approval_status IN ('draft','requested')")->fetchColumn();
    $dashboard['membership_requests'] = (int)mmDb()->query("SELECT COUNT(*) FROM elite_membership_requests WHERE request_status = 'open'")->fetchColumn();
}

mmHeader('Backoffice', 'Geschützter MysteryMarket Backoffice-Bereich.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow"><?= $role === 'admin' ? 'Admin' : 'Elite Shopper' ?></p>
    <h1><?= $role === 'admin' ? 'Little Backoffice.' : 'Elite Shopper Area.' ?></h1>
    <p class="lead">
      <?= $role === 'admin'
          ? 'Mitglieder, Ausweise, interne Informationen und operative Freigaben werden hier schrittweise gebündelt.'
          : 'Dein geschützter Bereich für Mitgliedschaft, Informationen und künftig deinen Elite-Shopper-Ausweis.' ?>
    </p>
    <div class="actions">
      <form method="post" action="/backoffice/logout.php">
        <input type="hidden" name="csrf" value="<?= mmEscape(mmBackofficeCsrfToken()) ?>">
        <button type="submit" class="button secondary">Abmelden</button>
      </form>
    </div>
  </div>
</section>

<?php if ($role === 'admin'): ?>
<section class="section backoffice-stat-section">
  <div class="backoffice-stat-grid">
    <article><span><?= $dashboard['active_members'] ?></span><strong>Aktive Elite Shopper</strong></article>
    <article><span><?= $dashboard['feed_posts'] ?></span><strong>Feed-Posts</strong></article>
    <article><span><?= $dashboard['new_contacts'] ?></span><strong>Neue Kontakte</strong></article>
    <article><span><?= $dashboard['open_approvals'] ?></span><strong>Offene Freigaben</strong></article>
    <article><span><?= $dashboard['membership_requests'] ?></span><strong>Mitgliedschaftsanfragen</strong></article>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="backoffice-module-grid">
    <?php if ($role === 'admin'): ?>
      <a class="backoffice-module" href="/backoffice/members.php"><span>01</span><strong>Elite Shopper</strong><small>Mitglieder & Status</small></a>
      <a class="backoffice-module" href="/backoffice/membership-requests.php"><span>01B</span><strong>Mitgliedschaft</strong><small>Pause-/Beendigungsanfragen</small></a>
      <a class="backoffice-module" href="/backoffice/credentials.php"><span>02</span><strong>Ausweis-Service</strong><small>Verify · Druck · Wallet · Karte</small></a>
      <a class="backoffice-module" href="/backoffice/approvals.php"><span>03</span><strong>Kommunikation</strong><small>Agentur-Freigaben</small></a>
      <a class="backoffice-module" href="/backoffice/contacts.php"><span>04</span><strong>Kontakte</strong><small>Read-only Anfragen</small></a>
      <a class="backoffice-module" href="/backoffice/feed.php"><span>05</span><strong>Elite Feed</strong><small>Interne Hinweise</small></a>
      <a class="backoffice-module" href="/backoffice/agencies.php"><span>06</span><strong>Agenturen</strong><small>Stammdaten für Feed & Freigaben</small></a>
      <a class="backoffice-module" href="/backoffice/system.php"><span>07</span><strong>System</strong><small>Status & Audit Log</small></a>
    <?php else: ?>
      <a class="backoffice-module" href="/backoffice/profile.php"><span>01</span><strong>Mitgliedschaft</strong><small>Status & Profil</small></a>
      <article class="backoffice-module"><span>02</span><strong>Projekt-Ausweise</strong><small>Werden projektbezogen über Verify bereitgestellt</small></article>
      <a class="backoffice-module" href="/backoffice/feed.php"><span>03</span><strong>Elite Feed</strong><small>Interne Projekt- und Partnerinfos</small></a>
      <article class="backoffice-module"><span>04</span><strong>ShopperMatch</strong><small>Eigenständige Job-/Matching-Plattform</small></article>
    <?php endif; ?>
  </div>
</section>
<?php mmFooter(); ?>
