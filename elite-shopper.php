<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site.php';

$lang = mmLanguage();

$content = [
    'de' => [
        'title' => 'Elite Shopper Partner',
        'description' => 'MysteryMarket Elite Shopper Partner: erfahrene, zuverlässige Shopper und Auditoren für anspruchsvolle Projekte.',
        'eyebrow' => 'Elite Shopper Partner',
        'headline' => 'Für Shopper und Auditoren, die regelmäßig zuverlässig liefern.',
        'lead' => 'Du arbeitest bereits als Shopper oder Auditor, kennst Briefings, Deadlines und Nachweise und möchtest bei geeigneten Projekten als qualifizierter Partner eingebunden werden? MysteryMarket baut dafür einen kleinen, verlässlichen Partnerkreis auf.',
        'cta' => 'Interesse als Elite Shopper Partner',
        'criteria_title' => 'Was wir unter „Elite“ verstehen.',
        'criteria_intro' => 'Nicht möglichst viele Profile, sondern verlässliche operative Partner. Entscheidend sind Erfahrung, Qualität und planbare Zusammenarbeit.',
        'c1' => ['Regelmäßige Erfahrung','Du führst Mystery Visits, Audits, Checks oder verwandte Fieldwork-Aufträge nicht nur gelegentlich durch und kennst typische operative Abläufe.'],
        'c2' => ['Zuverlässigkeit','Terminabsprachen, Deadlines, Nachweise und Rückfragen werden verbindlich behandelt. Probleme werden früh kommuniziert statt am Ende sichtbar.'],
        'c3' => ['Interne Qualifizierung','Du bist bereit, dich für bestimmte Projektarten intern einweisen oder zertifizieren zu lassen und projektspezifische Standards einzuhalten.'],
        'c4' => ['Eigenständige Zusammenarbeit','Du arbeitest selbständig und projektbezogen auf eigene Rechnung. Eine Aufnahme in den Partnerkreis ist keine Garantie für ein bestimmtes Auftragsvolumen.'],
        'infra_title' => 'Mehr als nur ein Auftrag.',
        'infra_text' => 'Geeignete Elite Shopper Partner können perspektivisch operative Infrastruktur von MysteryMarket und INSODEMA nutzen. Dazu gehört insbesondere OPS für Planung, Priorisierung, Regionen, Termine und mobile Field-Operations. Ziel ist nicht, dir zusätzliche Administration aufzubürden, sondern gute Feldarbeit besser planbar zu machen.',
        'agency_title' => 'Signal an Agenturen.',
        'agency_text' => 'Für Agenturpartner bedeutet der Elite-Shopper-Kreis: MysteryMarket kann bei geeigneten Projekten auf bereits bekannte, qualitätsorientierte und operativ erprobte Partner zurückgreifen. Die Projektführung, Methodik und Kundenbeziehung der Agentur bleiben davon unberührt.',
        'process_title' => 'So beginnt die Zusammenarbeit.',
        'p1' => ['01','Kurz vorstellen','Erfahrung, Regionen, typische Projektarten und vorhandene Plattformprofile.'],
        'p2' => ['02','Qualifizieren','Abgleich von Erfahrung, Verfügbarkeit und – wenn sinnvoll – interne Einweisung für bestimmte Projektarten.'],
        'p3' => ['03','Projektbezogen arbeiten','Zusammenarbeit bei passenden Projekten, transparenten Konditionen und klaren operativen Anforderungen.'],
        'mail' => 'eliteshopper@mysterymarket.de',
    ],
    'en' => [
        'title' => 'Elite Shopper Partners',
        'description' => 'MysteryMarket Elite Shopper Partners: experienced and reliable shoppers and auditors for demanding field projects.',
        'eyebrow' => 'Elite Shopper Partners',
        'headline' => 'For shoppers and auditors who deliver reliably, project after project.',
        'lead' => 'You already work as a shopper or auditor, understand briefs, deadlines and evidence requirements, and want to be considered for suitable projects as a qualified partner? MysteryMarket is building a focused network for exactly that.',
        'cta' => 'Become an Elite Shopper Partner',
        'criteria_title' => 'What “elite” means to us.',
        'criteria_intro' => 'Not the largest pool of profiles, but dependable operational partners. Experience, quality and predictable collaboration matter most.',
        'c1' => ['Regular experience','You perform mystery visits, audits, checks or related fieldwork regularly and understand the operational realities.'],
        'c2' => ['Reliability','Appointments, deadlines, evidence and questions are handled professionally. Problems are communicated early.'],
        'c3' => ['Internal qualification','You are willing to complete internal onboarding or certification for selected project types and follow project-specific standards.'],
        'c4' => ['Independent collaboration','You work independently and invoice your own services on a project basis. Membership does not guarantee a specific volume of assignments.'],
        'infra_title' => 'More than just another assignment.',
        'infra_text' => 'Selected Elite Shopper Partners may progressively use MysteryMarket and INSODEMA operational infrastructure, especially OPS for planning, prioritisation, regions, appointments and mobile field operations. The goal is less administration and better field execution.',
        'agency_title' => 'A signal to agencies.',
        'agency_text' => 'For agency partners, the Elite Shopper network means MysteryMarket can draw on known, quality-oriented and operationally proven partners when a project benefits from additional capacity. Agency methodology, project ownership and client relationships remain unaffected.',
        'process_title' => 'How collaboration starts.',
        'p1' => ['01','Introduce yourself','Tell us about your experience, regions, typical project types and existing platform profiles.'],
        'p2' => ['02','Qualify','We match experience and availability and, where useful, provide internal onboarding for selected project types.'],
        'p3' => ['03','Work project by project','Collaborate on suitable assignments with transparent conditions and clear operational requirements.'],
        'mail' => 'eliteshopper@mysterymarket.de',
    ],
    'nl' => [
        'title' => 'Elite Shopper Partners',
        'description' => 'MysteryMarket Elite Shopper Partners: ervaren en betrouwbare shoppers en auditors voor veeleisende fieldprojecten.',
        'eyebrow' => 'Elite Shopper Partners',
        'headline' => 'Voor shoppers en auditors die project na project betrouwbaar leveren.',
        'lead' => 'Werk je al regelmatig als shopper of auditor, ken je briefings, deadlines en bewijsvereisten en wil je voor passende projecten als gekwalificeerde partner worden ingezet? MysteryMarket bouwt hiervoor een kleine, betrouwbare partnergroep op.',
        'cta' => 'Interesse als Elite Shopper Partner',
        'criteria_title' => 'Wat “elite” voor ons betekent.',
        'criteria_intro' => 'Niet zoveel mogelijk profielen, maar betrouwbare operationele partners. Ervaring, kwaliteit en voorspelbare samenwerking staan centraal.',
        'c1' => ['Regelmatige ervaring','Je voert mystery visits, audits, checks of vergelijkbaar fieldwork regelmatig uit en kent de operationele praktijk.'],
        'c2' => ['Betrouwbaarheid','Afspraken, deadlines, bewijs en vragen worden professioneel behandeld. Problemen worden vroeg gemeld.'],
        'c3' => ['Interne kwalificatie','Je bent bereid interne onboarding of certificering voor bepaalde projecttypen te volgen en projectspecifieke standaarden te hanteren.'],
        'c4' => ['Zelfstandige samenwerking','Je werkt zelfstandig en factureert projectmatig voor eigen rekening. Deelname garandeert geen bepaald opdrachtenvolume.'],
        'infra_title' => 'Meer dan alleen een opdracht.',
        'infra_text' => 'Geselecteerde Elite Shopper Partners kunnen op termijn operationele infrastructuur van MysteryMarket en INSODEMA gebruiken, in het bijzonder OPS voor planning, prioritering, regio’s, afspraken en mobiele field operations. Het doel is minder administratie en beter uitvoerbaar fieldwork.',
        'agency_title' => 'Een signaal aan bureaus.',
        'agency_text' => 'Voor bureaupartners betekent het Elite Shopper-netwerk dat MysteryMarket bij passende projecten kan terugvallen op bekende, kwaliteitsgerichte en operationeel bewezen partners. Methodiek, projectleiding en klantrelatie van het bureau blijven ongewijzigd.',
        'process_title' => 'Zo begint de samenwerking.',
        'p1' => ['01','Stel jezelf kort voor','Vertel ons over ervaring, regio’s, typische projecttypen en bestaande platformprofielen.'],
        'p2' => ['02','Kwalificeren','We stemmen ervaring en beschikbaarheid af en bieden waar zinvol interne onboarding voor specifieke projecten.'],
        'p3' => ['03','Projectmatig samenwerken','Samenwerken aan passende opdrachten met transparante voorwaarden en duidelijke operationele eisen.'],
        'mail' => 'eliteshopper@mysterymarket.de',
    ],
];

$c = $content[$lang] ?? $content['de'];
mmHeader($c['title'], $c['description']);
?>
<section class="hero elite-hero">
  <div>
    <p class="eyebrow"><?= mmEscape($c['eyebrow']) ?></p>
    <h1><?= mmEscape($c['headline']) ?></h1>
    <p class="lead"><?= mmEscape($c['lead']) ?></p>
    <div class="actions">
      <a class="button" href="mailto:<?= mmEscape($c['mail']) ?>?subject=Elite%20Shopper%20Partner"><?= mmEscape($c['cta']) ?></a>
      <a class="button secondary" href="<?= mmEscape(mmLangUrl('/tools.php')) ?>">OPS ansehen</a>
    </div>
  </div>
  <div class="language-switcher" aria-label="Language">
    <span>Language</span>
    <a href="/elite-shopper.php?lang=de"<?= $lang === 'de' ? ' aria-current="page"' : '' ?>>DE</a>
    <a href="/elite-shopper.php?lang=en"<?= $lang === 'en' ? ' aria-current="page"' : '' ?>>EN</a>
    <a href="/elite-shopper.php?lang=nl"<?= $lang === 'nl' ? ' aria-current="page"' : '' ?>>NL</a>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow">Qualification</p>
    <h2><?= mmEscape($c['criteria_title']) ?></h2>
    <p class="lead"><?= mmEscape($c['criteria_intro']) ?></p>
  </div>
  <div class="elite-grid">
    <?php foreach (['c1','c2','c3','c4'] as $key): ?>
      <article class="elite-card">
        <h3><?= mmEscape($c[$key][0]) ?></h3>
        <p><?= mmEscape($c[$key][1]) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section">
  <div class="grid two">
    <article class="feature-panel">
      <span class="badge">OPS · INSODEMA</span>
      <h2><?= mmEscape($c['infra_title']) ?></h2>
      <p><?= mmEscape($c['infra_text']) ?></p>
      <a href="<?= mmEscape(mmLangUrl('/tools.php')) ?>">OPS →</a>
    </article>
    <article class="feature-panel agency-panel">
      <span class="badge">Agency Partner Signal</span>
      <h2><?= mmEscape($c['agency_title']) ?></h2>
      <p><?= mmEscape($c['agency_text']) ?></p>
      <a href="mailto:agency@mysterymarket.de">agency@mysterymarket.de</a>
    </article>
  </div>
</section>

<section class="section">
  <div class="section-head">
    <p class="eyebrow">Partner Path</p>
    <h2><?= mmEscape($c['process_title']) ?></h2>
  </div>
  <div class="process-grid">
    <?php foreach (['p1','p2','p3'] as $key): ?>
      <article>
        <span><?= mmEscape($c[$key][0]) ?></span>
        <h3><?= mmEscape($c[$key][1]) ?></h3>
        <p><?= mmEscape($c[$key][2]) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php mmFooter(); ?>
