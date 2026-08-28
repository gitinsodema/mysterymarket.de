<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$c = mmPageCopy('ops');
mmHeader($c['title'], $c['lead']);
?>
<section class="hero">
  <div><p class="eyebrow">INSODEMA Tool · Operational Planning</p><h1>OPS · Operations Suite</h1><p class="lead"><?= mmEscape($c['lead']) ?></p><div class="actions"><a class="button" href="https://test.insodema.com" target="_blank" rel="noopener noreferrer"><?= mmEscape($c['cta']) ?></a></div></div>
  <div class="notice"><strong><?= mmEscape($c['status']) ?></strong><p><?= mmEscape($c['status_text']) ?></p></div>
</section>
<section class="section"><div class="section-head"><p class="eyebrow"><?= mmEscape($c['why']) ?></p><h2><?= mmEscape($c['why_title']) ?></h2></div>
<div class="grid"><article class="card"><h3><?= mmEscape($c['plan']) ?></h3><p><?= mmEscape($c['plan_text']) ?></p></article><article class="card"><h3><?= mmEscape($c['prio']) ?></h3><p><?= mmEscape($c['prio_text']) ?></p></article><article class="card"><h3><?= mmEscape($c['mobile']) ?></h3><p><?= mmEscape($c['mobile_text']) ?></p></article></div></section>
<section class="section"><div class="grid two"><article class="card"><span class="badge">Practice</span><h3><?= mmEscape($c['practice']) ?></h3><p><?= mmEscape($c['practice_text']) ?></p></article><article class="card"><span class="badge">INSODEMA</span><h3><?= mmEscape($c['lab']) ?></h3><p><?= mmEscape($c['lab_text']) ?></p></article></div></section>
<?php mmFooter(); ?>
