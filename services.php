<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
mmHeader('Leistungen', 'Audit-, Inspektions- und Field-Service-Leistungen von MysteryMarket.');
?>
<section class="hero"><div><p class="eyebrow">Leistungen</p><h1>Field Audits mit operativem Fokus.</h1><p class="lead">Strukturierte Durchführung vor Ort, nachvollziehbare Dokumentation und klare Projektvorgaben.</p></div></section>
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
<?php mmFooter(); ?>
