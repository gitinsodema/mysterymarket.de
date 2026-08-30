<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site.php';

http_response_code(404);

$lang = mmLanguage();
$copy = [
    'de' => [
        'title' => '404 · Nicht im Briefing',
        'eyebrow' => '404 · Field Exception',
        'headline' => 'Diese Seite steht nicht im Briefing.',
        'lead' => 'Der angeforderte Pfad konnte nicht verifiziert werden. Im Audit wäre das jetzt eine Abweichung – hier reicht zum Glück ein neuer Einstieg.',
        'quote' => 'Gute Fieldwork-Regel: Wenn der Standort nicht stimmt, nicht improvisieren – erst verifizieren.',
        'home' => 'Zur Startseite',
        'verify' => 'Audit verifizieren',
        'services' => 'Leistungen ansehen',
    ],
    'en' => [
        'title' => '404 · Not in the brief',
        'eyebrow' => '404 · Field Exception',
        'headline' => 'This page is not in the brief.',
        'lead' => 'The requested path could not be verified. In an audit that would be a deviation – here, a new starting point is enough.',
        'quote' => 'Good fieldwork rule: if the location does not match, do not improvise – verify first.',
        'home' => 'Back to home',
        'verify' => 'Verify an audit',
        'services' => 'View services',
    ],
    'nl' => [
        'title' => '404 · Niet in de briefing',
        'eyebrow' => '404 · Field Exception',
        'headline' => 'Deze pagina staat niet in de briefing.',
        'lead' => 'Het gevraagde pad kon niet worden geverifieerd. Bij een audit zou dat een afwijking zijn – hier volstaat een nieuw startpunt.',
        'quote' => 'Goede fieldwork-regel: klopt de locatie niet, improviseer dan niet – eerst verifiëren.',
        'home' => 'Naar home',
        'verify' => 'Audit verifiëren',
        'services' => 'Diensten bekijken',
    ],
];

$c = $copy[$lang] ?? $copy['de'];
mmHeader($c['title'], $c['lead'], 'noindex,follow');
?>
<section class="not-found">
  <div class="not-found-code" aria-hidden="true">404</div>
  <div class="not-found-copy">
    <p class="eyebrow"><?= mmEscape($c['eyebrow']) ?></p>
    <h1><?= mmEscape($c['headline']) ?></h1>
    <p class="lead"><?= mmEscape($c['lead']) ?></p>
    <div class="audit-quote"><?= mmEscape($c['quote']) ?></div>
    <div class="actions">
      <a class="button" href="<?= mmEscape(mmLangUrl('/')) ?>"><?= mmEscape($c['home']) ?></a>
      <a class="button secondary" href="<?= mmEscape(mmLangUrl('/verify')) ?>"><?= mmEscape($c['verify']) ?></a>
      <a class="button secondary" href="<?= mmEscape(mmLangUrl('/services.php')) ?>"><?= mmEscape($c['services']) ?></a>
    </div>
  </div>
</section>
<?php mmFooter(); ?>
