<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$c = mmPageCopy('ops');
mmHeader($c['title'], $c['lead']);
?>
<section class="hero">
  <div>
    <p class="eyebrow">INSODEMA Tool · Operational Planning</p>
    <h1>OPS · Operations Suite</h1>
    <p class="lead"><?= mmEscape($c['lead']) ?></p>
    <div class="ops-pill-row" aria-label="OPS capabilities">
      <span>Day Planning</span><span>Prioritisation</span><span>Routing</span><span>OPS Connect</span>
    </div>
    <div class="actions"><a class="button" href="https://test.insodema.com" target="_blank" rel="noopener noreferrer"><?= mmEscape($c['cta']) ?></a></div>
  </div>
  <div class="notice"><strong><?= mmEscape($c['status']) ?></strong><p><?= mmEscape($c['status_text']) ?></p></div>
</section>

<section class="section ops-preview-section">
  <div class="section-head"><p class="eyebrow"><?= mmEscape($c['preview']) ?></p><h2><?= mmEscape($c['preview_title']) ?></h2><p class="lead"><?= mmEscape($c['preview_lead']) ?></p></div>
  <div class="ops-preview-stage" aria-label="<?= mmEscape($c['preview_title']) ?>">
    <div class="ops-desktop">
      <div class="ops-window-bar"><span></span><span></span><span></span><strong><?= mmEscape($c['desktop_label']) ?></strong></div>
      <div class="ops-window-body">
        <aside class="ops-side">
          <strong>OPS</strong>
          <span>Dispatcher</span><span>Assignments</span><span>Regions</span><span>Calendar</span>
        </aside>
        <div class="ops-dashboard">
          <div class="ops-dashboard-top">
            <div><small><?= mmEscape($c['desktop_region']) ?></small><strong><?= mmEscape($c['desktop_region_value']) ?></strong></div>
            <div><small><?= mmEscape($c['desktop_today']) ?></small><strong><?= mmEscape($c['desktop_jobs']) ?></strong></div>
            <div><small><?= mmEscape($c['desktop_anchor']) ?></small><strong><?= mmEscape($c['desktop_anchor_value']) ?></strong></div>
          </div>
          <div class="ops-recommendation">
            <small><?= mmEscape($c['desktop_next']) ?></small>
            <strong><?= mmEscape($c['desktop_next_value']) ?></strong>
            <span><?= mmEscape($c['desktop_reason']) ?></span>
          </div>
          <div class="ops-route-lines"><i></i><i></i><i></i><i></i></div>
        </div>
      </div>
    </div>
    <div class="ops-phone ops-phone-a">
      <div class="ops-phone-top"><?= mmEscape($c['mobile_label']) ?></div>
      <div class="ops-phone-screen">
        <small><?= mmEscape($c['mobile_question']) ?></small>
        <strong><?= mmEscape($c['mobile_next']) ?></strong>
        <div class="ops-mobile-card">
          <b><?= mmEscape($c['mobile_job']) ?></b>
          <span><?= mmEscape($c['mobile_place']) ?></span>
          <em><?= mmEscape($c['mobile_window']) ?></em>
        </div>
        <div class="ops-mobile-button"><?= mmEscape($c['mobile_action']) ?></div>
      </div>
    </div>
    <div class="ops-phone ops-phone-b">
      <div class="ops-phone-top">Daily Tasks</div>
      <div class="ops-phone-screen compact">
        <small>14:30</small><strong>Düsseldorf</strong>
        <div class="ops-task-line"><span></span><b>Anchor</b></div>
        <div class="ops-task-line"><span></span><b>Next</b></div>
        <div class="ops-task-line"><span></span><b>Later</b></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="section-head"><p class="eyebrow"><?= mmEscape($c['why']) ?></p><h2><?= mmEscape($c['why_title']) ?></h2></div>
  <div class="grid">
    <article class="card"><h3><?= mmEscape($c['plan']) ?></h3><p><?= mmEscape($c['plan_text']) ?></p></article>
    <article class="card"><h3><?= mmEscape($c['prio']) ?></h3><p><?= mmEscape($c['prio_text']) ?></p></article>
    <article class="card"><h3><?= mmEscape($c['mobile']) ?></h3><p><?= mmEscape($c['mobile_text']) ?></p></article>
  </div>
</section>

<section class="section">
  <div class="section-head"><p class="eyebrow"><?= mmEscape($c['benefits']) ?></p><h2><?= mmEscape($c['benefits_title']) ?></h2><p class="lead"><?= mmEscape($c['benefits_lead']) ?></p></div>
  <div class="ops-benefit-grid">
    <?php foreach ([
      [$c['benefit1'],$c['benefit1_text'],'01'],
      [$c['benefit2'],$c['benefit2_text'],'02'],
      [$c['benefit3'],$c['benefit3_text'],'03'],
      [$c['benefit4'],$c['benefit4_text'],'04'],
    ] as [$title,$text,$number]): ?>
      <article class="ops-benefit-card"><span><?= mmEscape($number) ?></span><h3><?= mmEscape($title) ?></h3><p><?= mmEscape($text) ?></p></article>
    <?php endforeach; ?>
  </div>
</section>



<section class="section ops-connect-section">
  <div class="ops-connect-copy">
    <p class="eyebrow"><?= mmEscape($c['connect']) ?></p>
    <h2><?= mmEscape($c['connect_title']) ?></h2>
    <p class="lead"><?= mmEscape($c['connect_lead']) ?></p>
    <p><?= mmEscape($c['connect_note']) ?></p>
    <div class="notice"><strong><?= mmEscape($c['connect_custom']) ?></strong><p><?= mmEscape($c['connect_custom_text']) ?></p></div>
  </div>
  <div class="ops-bridge-flow" aria-label="OPS Connect Bridges">
    <div class="ops-bridge-row">
      <div class="ops-bridge-endpoint"><small>Company A</small><strong>Order System</strong><span>Assignments · Dates · Locations</span></div>
      <div class="ops-bridge-arrow">→</div>
      <div class="ops-bridge-core"><strong>OPS</strong><span>Bridge</span></div>
      <div class="ops-bridge-arrow">→</div>
      <div class="ops-bridge-endpoint"><small>Fieldwork</small><strong>Audit App</strong><span>Mobile execution</span></div>
    </div>
    <div class="ops-bridge-return">API / structured result return ↩</div>
    <div class="ops-bridge-examples">
      <div><small>Example</small><strong>Company B → OPS</strong><span>CSV / Excel import for planning</span></div>
      <div><small>Example</small><strong>OPS → Client API</strong><span>Status, completion and selected result data</span></div>
      <div><small>Example</small><strong>Custom Bridge</strong><span>Project-specific import / export workflow</span></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="section-head"><p class="eyebrow"><?= mmEscape($c['audiences']) ?></p><h2><?= mmEscape($c['audiences_title']) ?></h2></div>
  <div class="grid">
    <article class="card"><h3><?= mmEscape($c['auditor']) ?></h3><p><?= mmEscape($c['auditor_text']) ?></p></article>
    <article class="card"><h3><?= mmEscape($c['agency']) ?></h3><p><?= mmEscape($c['agency_text']) ?></p></article>
    <article class="card"><h3><?= mmEscape($c['client']) ?></h3><p><?= mmEscape($c['client_text']) ?></p></article>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="card"><span class="badge">Practice</span><h3><?= mmEscape($c['practice']) ?></h3><p><?= mmEscape($c['practice_text']) ?></p></article>
    <article class="card"><span class="badge">INSODEMA</span><h3><?= mmEscape($c['lab']) ?></h3><p><?= mmEscape($c['lab_text']) ?></p></article>
  </div>
</section>
<?php mmFooter(); ?>
