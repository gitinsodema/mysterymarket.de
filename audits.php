<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$c = mmPageCopy('audits');
mmHeader($c['title'], $c['lead']);
?>
<section class="hero"><div><p class="eyebrow"><?= mmEscape($c['title']) ?></p><h1><?= mmEscape($c['hero']) ?></h1><p class="lead"><?= mmEscape($c['lead']) ?></p></div></section>
<section class="section">
  <div class="partner-grid">
    <div class="partner-card"><span><?= mmEscape($c['audit_partner']) ?></span><strong>SKOPOS NEXT</strong></div>
    <div class="partner-card"><span><?= mmEscape($c['client']) ?></span><strong>Vodafone</strong></div>
    <div class="partner-card"><span><?= mmEscape($c['audit_partner']) ?></span><strong>BARE International</strong></div>
    <div class="partner-card"><span><?= mmEscape($c['client']) ?></span><strong>HP</strong></div>
  </div>
</section>
<section class="section"><div class="grid two">
  <article class="card"><span class="status"><?= mmEscape($c['active']) ?></span><h3>Vodafone · Datenschutz Audits</h3><p><strong><?= mmEscape($c['audit_partner']) ?>:</strong> SKOPOS NEXT</p><p><?= mmEscape($c['vodafone']) ?></p></article>
  <article class="card"><span class="status"><?= mmEscape($c['ongoing']) ?></span><h3>HP · Brand & Retail Audits</h3><p><strong><?= mmEscape($c['audit_partner']) ?>:</strong> BARE International</p><p><?= mmEscape($c['hp']) ?></p></article>
</div></section>
<section class="section"><div class="notice"><strong><?= mmEscape($c['conf']) ?></strong><p><?= mmEscape($c['conf_text']) ?></p></div></section>
<?php mmFooter(); ?>
