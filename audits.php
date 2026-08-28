<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
mmHeader('Aktuelle Audits', 'Öffentlich freigegebene aktuelle Auditprogramme und Agenturpartner von MysteryMarket.');
?>
<section class="hero">
  <div>
    <p class="eyebrow">Transparenz</p>
    <h1>Aktuelle Auditprogramme.</h1>
    <p class="lead">MysteryMarket arbeitet projektbezogen mit spezialisierten Agenturen und Auftraggebern. Öffentlich zeigen wir ausschließlich freigegebene Projektkontexte.</p>
  </div>
</section>

<section class="section">
  <div class="partner-grid">
    <div class="partner-card"><span>Audit Partner</span><strong>SKOPOS NEXT</strong></div>
    <div class="partner-card"><span>Projektkunde</span><strong>Vodafone</strong></div>
    <div class="partner-card"><span>Audit Partner</span><strong>BARE International</strong></div>
    <div class="partner-card"><span>Projektkunde</span><strong>HP</strong></div>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="card">
      <span class="status">Aktives Auditprogramm</span>
      <h3>Vodafone · Datenschutz Audits</h3>
      <p><strong>Audit Partner:</strong> SKOPOS NEXT</p>
      <p>Angekündigte Vor-Ort-Prüfungen im Rahmen eines Datenschutz-Auditprogramms.</p>
    </article>
    <article class="card">
      <span class="status">Laufendes Auditprogramm</span>
      <h3>HP · Brand & Retail Audits</h3>
      <p><strong>Audit Partner:</strong> BARE International</p>
      <p>Strukturierte Brand-, Retail- und Equipment-Audits am Point of Sale.</p>
    </article>
  </div>
</section>

<section class="section">
  <div class="notice">
    <strong>Vertraulichkeit gehört zur Audit-Arbeit.</strong>
    <p>Nicht sämtliche von MysteryMarket durchgeführten Projekte, Kunden und Auftraggeber werden öffentlich genannt. Bei vertraulichen Programmen erfolgt eine Darstellung ausschließlich im freigegebenen Umfang.</p>
  </div>
</section>
<?php mmFooter(); ?>
