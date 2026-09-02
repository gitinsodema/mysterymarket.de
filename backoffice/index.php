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
    'credential_projects' => 0,
    'credential_count' => 0,
    'project_requests' => 0,
];

if ($role === 'admin') {
    $dashboard['active_members'] = (int)mmDb()->query("SELECT COUNT(*) FROM elite_members WHERE membership_status = 'active'")->fetchColumn();
    $dashboard['feed_posts'] = (int)mmDb()->query("SELECT COUNT(*) FROM elite_feed_posts WHERE is_active = 1")->fetchColumn();
    $dashboard['new_contacts'] = (int)mmDb()->query("SELECT COUNT(*) FROM contact_requests WHERE status = 'new'")->fetchColumn();
    $dashboard['open_approvals'] = (int)mmDb()->query("SELECT COUNT(*) FROM agency_approvals WHERE approval_status IN ('draft','requested')")->fetchColumn();
    $dashboard['membership_requests'] = (int)mmDb()->query("SELECT COUNT(*) FROM elite_membership_requests WHERE request_status = 'open'")->fetchColumn();
    $dashboard['credential_projects'] = (int)mmDb()->query("SELECT COUNT(*) FROM credential_projects WHERE is_active = 1")->fetchColumn();
    $dashboard['credential_count'] = (int)mmDb()->query("SELECT COUNT(*) FROM audit_verifications WHERE is_personal_verification = 1")->fetchColumn();
    $dashboard['project_requests'] = (int)mmDb()->query("SELECT COUNT(*) FROM credential_project_requests WHERE request_status = 'pending'")->fetchColumn();
}

mmHeader('Backoffice', 'Geschützter MysteryMarket Backoffice-Bereich.', 'noindex,nofollow');
?>
<section class="hero backoffice-dashboard-hero">
  <div>
    <p class="eyebrow"><?= $role === 'admin' ? 'Admin' : 'Elite Shopper' ?></p>
    <h1><?= $role === 'admin' ? 'Backoffice.' : 'Elite Shopper Area.' ?></h1>
    <p class="lead">
      <?= $role === 'admin'
          ? 'Verwaltung von Mitgliedern, Projekten, Ausweisen, Kontakten und Freigaben.'
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

<section class="section backoffice-install-hint" data-backoffice-install-hint hidden>
  <div class="notice">
    <strong>Auf dem iPhone installieren:</strong>
    In Safari auf „Teilen“ tippen und „Zum Home-Bildschirm“ wählen. Danach startet das Backoffice wie eine eigene App im Standalone-Modus.
  </div>
</section>



<section class="section">
  <div class="backoffice-module-grid">
    <?php if ($role === 'admin'): ?>
      <a class="backoffice-module" href="/backoffice/members.php"><span>01</span><b class="backoffice-module-count"><?= $dashboard['active_members'] ?></b><strong>Elite Shopper</strong><small>Aktive Mitglieder & Status</small></a>
      <a class="backoffice-module" href="/backoffice/membership-requests.php"><span>01B</span><b class="backoffice-module-count"><?= $dashboard['membership_requests'] ?></b><strong>Mitgliedschaft</strong><small>Offene Anfragen</small></a>
      <a class="backoffice-module" href="/backoffice/credentials.php"><span>02</span><b class="backoffice-module-count"><?= $dashboard['credential_count'] ?></b><strong>Ausweis-Service</strong><small>Verify · Druck · Wallet · Karte</small></a>
      <a class="backoffice-module" href="/backoffice/credential-projects.php"><span>02B</span><b class="backoffice-module-count"><?= $dashboard['project_requests'] ?></b><strong>Projekte</strong><small>Stammdaten · offene Vorschläge</small></a>
      <a class="backoffice-module" href="/backoffice/approvals.php"><span>03</span><b class="backoffice-module-count"><?= $dashboard['open_approvals'] ?></b><strong>Kommunikation</strong><small>Offene Agentur-Freigaben</small></a>
      <a class="backoffice-module" href="/backoffice/contacts.php"><span>04</span><b class="backoffice-module-count"><?= $dashboard['new_contacts'] ?></b><strong>Kontakte</strong><small>Neue & laufende Anfragen</small></a>
      <a class="backoffice-module" href="/backoffice/feed.php"><span>05</span><b class="backoffice-module-count"><?= $dashboard['feed_posts'] ?></b><strong>Elite Feed</strong><small>Aktive interne Hinweise</small></a>
      <a class="backoffice-module" href="/backoffice/agencies.php"><span>06</span><strong>Agenturen</strong><small>Stammdaten für Projekte & Freigaben</small></a>
      <a class="backoffice-module" href="/backoffice/system.php"><span>07</span><strong>System</strong><small>Status & Audit Log</small></a>
    <?php else: ?>
      <a class="backoffice-module" href="/backoffice/profile.php"><span>01</span><strong>Mitgliedschaft</strong><small>Status & Profil</small></a>
      <a class="backoffice-module" href="/backoffice/profile.php"><span>02</span><strong>Meine Ausweise</strong><small>Projektbezogene Verify-Ausweise</small></a>
      <a class="backoffice-module" href="/backoffice/project-request-new.php"><span>03</span><strong>Projekt vorschlagen</strong><small>Agentur + Projekt + Legitimationsschreiben</small></a>
      <a class="backoffice-module" href="/backoffice/feed.php"><span>04</span><strong>Elite Feed</strong><small>Interne Projekt- und Partnerinfos</small></a>
      <article class="backoffice-module"><span>05</span><strong>ShopperMatch</strong><small>Eigenständige Job-/Matching-Plattform</small></article>
    <?php endif; ?>
  </div>
</section>
<?php mmFooter(); ?>
