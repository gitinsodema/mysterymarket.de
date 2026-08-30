<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$c = mmPageCopy('services');
mmHeader($c['title'], $c['lead']);
?>
<section class="hero">
  <div><p class="eyebrow"><?= mmEscape($c['title']) ?></p><h1><?= mmEscape($c['hero']) ?></h1><p class="lead"><?= mmEscape($c['lead']) ?></p></div>
  <div class="notice"><strong><?= mmEscape($c['roles']) ?></strong><p><?= mmEscape($c['roles_text']) ?></p></div>
</section>
<section class="section">
  <div class="section-head"><p class="eyebrow">Audit & Field Services</p><h2><?= mmEscape($c['section']) ?></h2></div>
  <div class="service-grid">
  <?php foreach (mmServiceItems() as $i => $service): ?>
    <article class="service-card service-card-visual service-tone-<?= ($i % 4) + 1 ?>">
      <span class="service-number"><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span>
      <div class="service-icon" aria-hidden="true"><?= mmServiceIconSvg((string)$service[0]) ?></div>
      <p class="eyebrow"><?= mmEscape($service[0]) ?></p>
      <h3><?= mmEscape($service[1]) ?></h3>
      <p><?= mmEscape($service[2]) ?></p>
      <ul><?php foreach ($service[3] as $item): ?><li><?= mmEscape($item) ?></li><?php endforeach; ?></ul>
    </article>
  <?php endforeach; ?>
  </div>
</section>
<section class="section">
  <div class="section-head"><p class="eyebrow"><?= mmEscape($c['agency']) ?></p><h2><?= mmEscape($c['agency_title']) ?></h2></div>
  <div class="grid two">
    <article class="card"><h3><?= mmEscape($c['agency1']) ?></h3><p><?= mmEscape($c['agency1_text']) ?></p></article>
    <article class="card"><h3><?= mmEscape($c['agency2']) ?></h3><p><?= mmEscape($c['agency2_text']) ?></p></article>
  </div>
  <div class="actions"><?= mmEmailLink('agency', mmEscape($c['cta'])) ?></div>
</section>
<?php mmFooter(); ?>
