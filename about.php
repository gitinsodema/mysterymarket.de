<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$c = mmPageCopy('about');
mmHeader($c['title'], 'MysteryMarket Audit & Field Services.');
?>
<section class="hero">
  <div><p class="eyebrow">About</p><h1>MysteryMarket</h1><p class="lead"><strong>Independent Audit & Field Services</strong><br>Asset & Equipment Inspections · Brand Audits · Compliance Audits · Quality Assurance · Revenue Protection · Process & Field Audits · Mystery Audits · Mystery Visits</p></div>
  <div class="card"><p><strong>Düsseldorf / Germany</strong></p><p>Chief Audit Executive<br><strong>Robert Breuss</strong></p><p><?= mmEscape($c['general']) ?>:<br><a href="mailto:hello@mysterymarket.de">hello@mysterymarket.de</a></p><p><?= mmEscape($c['agency']) ?>:<br><a href="mailto:agency@mysterymarket.de">agency@mysterymarket.de</a></p><p><?= mmEscape($c['elite']) ?>:<br><a href="mailto:eliteshopper@mysterymarket.de">eliteshopper@mysterymarket.de</a></p><p>Shopmetrics ID: <strong>rbreuss</strong></p></div>
</section>
<section class="section"><div class="section-head"><p class="eyebrow"><?= mmEscape($c['work']) ?></p><h2><?= mmEscape($c['work_title']) ?></h2></div><p class="lead"><?= mmEscape($c['work_text']) ?></p></section>
<?php mmFooter(); ?>
