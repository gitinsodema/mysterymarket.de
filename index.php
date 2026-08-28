<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$c = mmPageCopy('home');
mmHeader($c['title'], $c['description']);
?>
<section class="hero">
  <div>
    <p class="eyebrow">Independent Audit & Field Services</p>
    <h1><?= mmEscape($c['hero']) ?></h1>
    <p class="lead"><?= mmEscape($c['lead']) ?></p>
    <div class="actions">
      <a class="button" href="<?= mmEscape(mmLangUrl('/services.php')) ?>"><?= mmEscape($c['cta_services']) ?></a>
      <a class="button secondary" href="<?= mmEscape(mmLangUrl('/contact.php')) ?>"><?= mmEscape($c['cta_project']) ?></a>
    </div>
  </div>
  <div class="notice">
    <strong><?= mmEscape($c['agency']) ?></strong>
    <p><?= mmEscape($c['agency_text']) ?></p>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow"><?= mmEscape($c['services']) ?></p>
    <h2><?= mmEscape($c['services_title']) ?></h2>
    <p class="lead"><?= mmEscape($c['services_lead']) ?></p>
  </div>
  <div class="service-teaser-grid">
    <?php foreach (mmServiceItems() as $i => $service): ?>
      <a href="<?= mmEscape(mmLangUrl('/services.php')) ?>" class="service-teaser"><span><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span><strong><?= mmEscape($service[1]) ?></strong></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow"><?= mmEscape($c['collab']) ?></p>
    <h2><?= mmEscape($c['collab_title']) ?></h2>
    <p class="lead"><?= mmEscape($c['collab_lead']) ?></p>
  </div>
  <div class="grid">
    <article class="card"><h3><?= mmEscape($c['agency']) ?></h3><p><?= mmEscape($c['agency_text']) ?></p><a href="mailto:agency@mysterymarket.de">agency@mysterymarket.de</a></article>
    <article class="card"><h3><?= mmEscape($c['direct']) ?></h3><p><?= mmEscape($c['direct_text']) ?></p><a href="<?= mmEscape(mmLangUrl('/contact.php')) ?>"><?= mmEscape($c['cta_project']) ?> →</a></article>
    <article class="card"><h3><?= mmEscape($c['elite']) ?></h3><p><?= mmEscape($c['elite_text']) ?></p><a href="<?= mmEscape(mmLangUrl('/elite-shopper.php')) ?>"><?= mmEscape($c['elite']) ?> →</a></article>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow"><?= mmEscape($c['coverage']) ?></p>
    <h2><?= mmEscape($c['coverage_title']) ?></h2>
    <p class="lead"><?= mmEscape($c['coverage_text']) ?></p>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow"><?= mmEscape($c['quality']) ?></p>
    <h2><?= mmEscape($c['quality_title']) ?></h2>
  </div>
  <div class="grid">
    <article class="card"><h3><?= mmEscape($c['quality1']) ?></h3><p><?= mmEscape($c['quality1_text']) ?></p></article>
    <article class="card"><h3><?= mmEscape($c['quality2']) ?></h3><p><?= mmEscape($c['quality2_text']) ?></p></article>
    <article class="card"><h3><?= mmEscape($c['quality3']) ?></h3><p><?= mmEscape($c['quality3_text']) ?></p></article>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow"><?= mmEscape($c['process']) ?></p>
    <h2><?= mmEscape($c['process_title']) ?></h2>
  </div>
  <div class="process-grid">
    <article><span>01</span><h3><?= mmEscape($c['process1']) ?></h3><p><?= mmEscape($c['process1_text']) ?></p></article>
    <article><span>02</span><h3><?= mmEscape($c['process2']) ?></h3><p><?= mmEscape($c['process2_text']) ?></p></article>
    <article><span>03</span><h3><?= mmEscape($c['process3']) ?></h3><p><?= mmEscape($c['process3_text']) ?></p></article>
  </div>
</section>

<section class="section elite-signal">
  <div>
    <p class="eyebrow"><?= mmEscape($c['signal']) ?></p>
    <h2><?= mmEscape($c['signal_title']) ?></h2>
    <p><?= mmEscape($c['signal_text']) ?></p>
  </div>
  <div class="actions"><a class="button" href="<?= mmEscape(mmLangUrl('/elite-shopper.php')) ?>"><?= mmEscape($c['cta_elite']) ?></a></div>
</section>

<section class="section">
  <div class="section-head"><p class="eyebrow"><?= mmEscape($c['projects']) ?></p><h2><?= mmEscape($c['projects_title']) ?></h2></div>
  <div class="partner-grid">
    <div class="partner-card"><span><?= mmEscape($c['audit_partner']) ?></span><strong>SKOPOS NEXT</strong></div>
    <div class="partner-card"><span><?= mmEscape($c['project_context']) ?></span><strong>Vodafone</strong></div>
    <div class="partner-card"><span><?= mmEscape($c['audit_partner']) ?></span><strong>Specialised Audit Partner</strong></div>
    <div class="partner-card"><span><?= mmEscape($c['project_context']) ?></span><strong>Brand & Retail Audits</strong></div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="card"><span class="badge">Verification</span><h3><?= mmEscape($c['verify_title']) ?></h3><p><?= mmEscape($c['verify_text']) ?></p><a href="<?= mmEscape(mmLangUrl('/verify.php')) ?>"><?= mmEscape($c['cta_verify']) ?> →</a></article>
    <article class="card"><span class="badge">INSODEMA Tool</span><h3>OPS · Operations Suite</h3><p><?= mmEscape($c['ops_text']) ?></p><a href="<?= mmEscape(mmLangUrl('/tools.php')) ?>"><?= mmEscape($c['cta_ops']) ?> →</a></article>
  </div>
</section>
<?php mmFooter(); ?>
