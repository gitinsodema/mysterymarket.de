<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$role = (string)$user['role'];

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

<section class="section">
  <div class="backoffice-module-grid">
    <?php if ($role === 'admin'): ?>
      <article class="backoffice-module"><span>01</span><strong>Elite Shopper</strong><small>Mitglieder & Status</small></article>
      <article class="backoffice-module"><span>02</span><strong>Credentials</strong><small>Ausweise & QR · R1.2</small></article>
      <article class="backoffice-module"><span>03</span><strong>Kommunikation</strong><small>Agentur-Freigaben</small></article>
      <article class="backoffice-module"><span>04</span><strong>Kontakte</strong><small>Read-only Anfragen</small></article>
      <article class="backoffice-module"><span>05</span><strong>Elite Feed</strong><small>Interne Hinweise</small></article>
      <article class="backoffice-module"><span>06</span><strong>Einstellungen</strong><small>Backoffice-Konfiguration</small></article>
    <?php else: ?>
      <article class="backoffice-module"><span>01</span><strong>Mitgliedschaft</strong><small>Status & Profil</small></article>
      <article class="backoffice-module"><span>02</span><strong>Mein Ausweis</strong><small>QR · Wallet · Karte folgt</small></article>
      <article class="backoffice-module"><span>03</span><strong>Elite Feed</strong><small>Interne Projekt- und Partnerinfos</small></article>
      <article class="backoffice-module"><span>04</span><strong>ShopperMatch</strong><small>Eigenständige Job-/Matching-Plattform</small></article>
    <?php endif; ?>
  </div>
</section>
<?php mmFooter(); ?>
