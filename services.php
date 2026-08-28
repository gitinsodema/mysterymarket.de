<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
mmHeader('Leistungen', 'Audit-, Inspektions- und Field-Service-Leistungen von MysteryMarket für Agenturen und Auftraggeber.');
?>
<section class="hero">
  <div>
    <p class="eyebrow">Leistungen</p>
    <h1>Operative Durchführung im Feld.</h1>
    <p class="lead">MysteryMarket arbeitet als ausführender Partner innerhalb definierter Projektstrukturen – für Agenturen, Research-Partner und direkte Auftraggeber.</p>
  </div>
  <div class="notice">
    <strong>Klare Rollen.</strong>
    <p>Wir übernehmen Fieldwork, Audits, Inspektionen und strukturierte Dokumentation. Methodik, zentrale Auswertung oder Reporting können vollständig beim beauftragenden Partner verbleiben.</p>
  </div>
</section>

<section class="section">
  <?php
  $services = [
    ['Inspections & Asset Audits','Asset & Equipment Inspections, Bestands- und Geräteprüfungen, Erfassung und Dokumentation des Ist-Zustands.'],
    ['Brand & Quality Audits','Brand Audits, Quality Assurance und Prüfung definierter Marken- und Qualitätsstandards.'],
    ['Compliance & Process Audits','Compliance Audits, Datenschutz-Audits und strukturierte Prozessprüfungen nach vorgegebenen Kriterien.'],
    ['Revenue Protection','Kontroll- und Erhebungsleistungen im Umfeld von Einnahmensicherung und operativen Prozessen.'],
    ['Process & Field Audits','Vor-Ort-Prüfungen, strukturierte Erhebungen und dokumentierte Prozessbeobachtungen.'],
    ['Mystery Audits & Mystery Visits','Verdeckte Prüfungen von Service-, Beratungs- und Prozessqualität nach definiertem Briefing.'],
  ];
  foreach ($services as [$name,$text]): ?>
    <div class="definition"><strong><?= mmEscape($name) ?></strong><div><?= mmEscape($text) ?></div></div>
  <?php endforeach; ?>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow">Für Agenturen</p>
    <h2>Zusätzliche operative Kapazität ohne Rollenverschiebung.</h2>
  </div>
  <div class="grid two">
    <article class="card"><h3>Projektvorgaben bleiben beim Partner</h3><p>Briefing, Fragebogen, Methodik, Reporting-Struktur und zentrale Kundenkommunikation können vollständig durch die beauftragende Agentur definiert werden.</p></article>
    <article class="card"><h3>Fieldwork bleibt nachvollziehbar</h3><p>Durchführung, Nachweise, Terminstatus und operative Besonderheiten werden strukturiert dokumentiert und im vereinbarten Prozess zurückgespielt.</p></article>
  </div>
</section>
<?php mmFooter(); ?>
