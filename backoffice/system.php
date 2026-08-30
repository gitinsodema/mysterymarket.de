<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$version = trim((string)@file_get_contents(dirname(__DIR__) . '/VERSION'));
$counts = [
    'Admins' => "SELECT COUNT(*) FROM backoffice_users WHERE role='admin' AND account_status='active'",
    'Elite Shopper' => "SELECT COUNT(*) FROM elite_members",
    'Aktive Elite Shopper' => "SELECT COUNT(*) FROM elite_members WHERE membership_status='active'",
    'Agenturen' => "SELECT COUNT(*) FROM agencies WHERE is_active=1",
    'Feed-Posts' => "SELECT COUNT(*) FROM elite_feed_posts WHERE is_active=1",
    'Neue Kontakte' => "SELECT COUNT(*) FROM contact_requests WHERE status='new'",
    'Offene Freigaben' => "SELECT COUNT(*) FROM agency_approvals WHERE approval_status IN ('draft','requested')",
    'Offene Mitgliedschaftsanfragen' => "SELECT COUNT(*) FROM elite_membership_requests WHERE request_status='open'",
];
$values=[];
foreach($counts as $label=>$sql){$values[$label]=(int)mmDb()->query($sql)->fetchColumn();}

mmHeader('System', 'MysteryMarket Backoffice-Systemübersicht.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow">Admin · System</p>
    <h1>Systemübersicht.</h1>
    <p class="lead">Kompakter Zustand des privaten MysteryMarket Backoffice.</p>
    <div class="actions">
      <a class="button" href="/backoffice/audit-log.php">Audit Log</a>
      <a class="button secondary" href="/backoffice/">Dashboard</a>
    </div>
  </div>
</section>
<section class="section">
  <div class="backoffice-stat-grid">
    <?php foreach($values as $label=>$value): ?>
      <article><span><?= $value ?></span><strong><?= mmEscape($label) ?></strong></article>
    <?php endforeach; ?>
  </div>
</section>
<section class="section">
  <div class="grid two">
    <article class="card">
      <span class="badge">Release</span>
      <h2><?= mmEscape($version !== '' ? $version : 'unbekannt') ?></h2>
      <p>Öffentliche Website-Version. R1.1 Backoffice wird weiterhin auf dem Entwicklungsbranch vorbereitet.</p>
    </article>
    <article class="card">
      <span class="badge">Runtime</span>
      <h3>PHP <?= mmEscape(PHP_VERSION) ?></h3>
      <p>Datenbank und interne Sicherheitskonfiguration werden absichtlich ohne Zugangsdaten oder Secrets angezeigt.</p>
    </article>
  </div>
</section>
<?php mmFooter(); ?>
