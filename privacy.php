<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$legal = mmLegal();
$c = mmPageCopy('privacy');
$lang = mmLanguage();

$text = [
'de'=>[
 'hosting'=>'Beim Aufruf der Website können technisch erforderliche Verbindungs- und Protokolldaten verarbeitet werden, insbesondere IP-Adresse, Zeitpunkt, angeforderte Ressource, Statuscode, Referrer sowie Browser- und Geräteinformationen. Zweck sind Auslieferung, Fehleranalyse und Schutz der Systeme. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.',
 'contact'=>'Das Kontaktformular verarbeitet Anfrageart, Name, Organisation, E-Mail, optionale Telefonnummer, Betreff und Nachricht. Die Daten werden ausschließlich zur Bearbeitung der jeweiligen Anfrage gespeichert. Allgemeine Anfragen werden über hello@mysterymarket.de bearbeitet. Rechtsgrundlage ist je nach Anfrage Art. 6 Abs. 1 lit. b oder f DSGVO.',
 'verify'=>'Bei der Prüfung einer Referenz wird der eingegebene Verifikationscode verarbeitet, um den Status eines Auditvorgangs anzuzeigen. Zum Schutz vor automatisierten oder missbräuchlichen Abfragen kann die IP-Adresse mit einem serverseitigen Geheimnis pseudonymisiert gehasht und dieser Hash zusammen mit dem Zeitpunkt kurzfristig für das Rate-Limiting gespeichert werden; diese Einträge werden spätestens nach 24 Stunden entfernt. Beim optionalen QR-Scan wird das Kamerabild ausschließlich lokal im Browser ausgewertet und nicht an MysteryMarket übertragen. Bei personenbezogenen Legitimationsdatensätzen können nach erfolgreicher Verifikation zusätzlich freigegebene Identitäts- und Projektangaben, ein Foto sowie geschützte Nachweisdokumente angezeigt werden. Diese Inhalte werden nicht als frei indexierbare Profilseite bereitgestellt. Öffentliche Ergebnisse enthalten nur für die Veröffentlichung freigegebene Informationen. Vertrauliche Auftraggeber- oder Projektdaten werden nicht offengelegt.',
 'cookies'=>'Die Website verwendet keine Marketing- oder Audience-Analytics-Cookies. Für sichere Formulare können technisch notwendige Sessions verwendet werden. Die Entscheidung im Cookie-Hinweis wird lokal im Browser gespeichert.',
 'recipients'=>'Personenbezogene Daten werden nur an Hosting- oder Systemdienstleister sowie berechtigte interne Systeme übermittelt, soweit dies für den jeweiligen Zweck erforderlich ist. Eine Weitergabe zu Werbezwecken findet nicht statt.',
 'retention'=>'Daten werden nur so lange gespeichert, wie dies für den jeweiligen Zweck, gesetzliche Pflichten oder die Abwehr bzw. Durchsetzung von Ansprüchen erforderlich ist.',
 'rights'=>'Sie haben im Rahmen der gesetzlichen Voraussetzungen insbesondere Rechte auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch. Außerdem besteht ein Beschwerderecht bei einer Datenschutzaufsichtsbehörde.',
 'security'=>'Formulare werden über HTTPS übertragen. Zugangsdaten und Datenbankverbindungen liegen ausschließlich serverseitig und werden nicht an den Browser ausgeliefert.',
],
'en'=>[
 'hosting'=>'When you access the website, technically necessary connection and log data may be processed, including IP address, time, requested resource, status code, referrer and browser or device information. The purposes are delivery, error analysis and system protection. The legal basis is Art. 6(1)(f) GDPR.',
 'contact'=>'The contact form processes enquiry type, name, organisation, email address, optional phone number, subject and message. Data is stored solely to handle the respective enquiry. General enquiries are handled via hello@mysterymarket.de. The legal basis is Art. 6(1)(b) or (f) GDPR, depending on the enquiry.',
 'verify'=>'When a reference is checked, the entered verification code is processed to display the status of an audit record. To protect against automated or abusive queries, the IP address may be pseudonymously hashed with a server-side secret and the resulting hash stored briefly with the timestamp for rate limiting; these records are removed after no more than 24 hours. When the optional QR scanner is used, camera images are processed locally in the browser and are not transmitted to MysteryMarket. For personal authorisation records, approved identity and project details, a photo and protected evidence documents may additionally be shown after successful verification. These contents are not provided as a freely indexable profile page. Public results contain only information approved for publication. Confidential client or project data is not disclosed.',
 'cookies'=>'The website does not use marketing or audience-analytics cookies. Technically necessary sessions may be used for secure forms. The cookie-setting choice is stored locally in the browser.',
 'recipients'=>'Personal data is only transferred to hosting or system service providers and authorised internal systems where required for the relevant purpose. Data is not shared for advertising purposes.',
 'retention'=>'Data is retained only for as long as required for the relevant purpose, legal obligations or the establishment, exercise or defence of claims.',
 'rights'=>'Subject to legal requirements, you have rights including access, rectification, erasure, restriction, data portability and objection. You also have the right to lodge a complaint with a data-protection supervisory authority.',
 'security'=>'Forms are transmitted via HTTPS. Credentials and database connections remain server-side and are not delivered to the browser.',
],
'nl'=>[
 'hosting'=>'Bij het openen van de website kunnen technisch noodzakelijke verbindings- en loggegevens worden verwerkt, waaronder IP-adres, tijdstip, opgevraagde resource, statuscode, referrer en browser- of apparaatgegevens. Doel is levering, foutanalyse en beveiliging van de systemen. Rechtsgrond is art. 6 lid 1 onder f AVG.',
 'contact'=>'Het contactformulier verwerkt type aanvraag, naam, organisatie, e-mailadres, optioneel telefoonnummer, onderwerp en bericht. De gegevens worden uitsluitend opgeslagen om de betreffende aanvraag te behandelen. Algemene vragen worden verwerkt via hello@mysterymarket.de. Rechtsgrond is afhankelijk van de aanvraag art. 6 lid 1 onder b of f AVG.',
 'verify'=>'Bij het controleren van een referentie wordt de ingevoerde verificatiecode verwerkt om de status van een auditdossier te tonen. Ter bescherming tegen geautomatiseerde of misbruikende aanvragen kan het IP-adres met een servergeheim gepseudonimiseerd worden gehasht en deze hash kort samen met het tijdstip voor rate limiting worden opgeslagen; deze gegevens worden uiterlijk na 24 uur verwijderd. Bij gebruik van de optionele QR-scanner worden camerabeelden uitsluitend lokaal in de browser verwerkt en niet naar MysteryMarket verzonden. Bij persoonlijke legitimatierecords kunnen na succesvolle verificatie ook vrijgegeven identiteits- en projectgegevens, een foto en beveiligde bewijsdocumenten worden getoond. Deze inhoud wordt niet als vrij indexeerbare profielpagina aangeboden. Openbare resultaten bevatten alleen informatie die voor publicatie is vrijgegeven. Vertrouwelijke klant- of projectgegevens worden niet bekendgemaakt.',
 'cookies'=>'De website gebruikt geen marketing- of audience-analyticscookies. Voor veilige formulieren kunnen technisch noodzakelijke sessies worden gebruikt. De keuze in de cookie-instellingen wordt lokaal in de browser opgeslagen.',
 'recipients'=>'Persoonsgegevens worden alleen doorgegeven aan hosting- of systeemdienstverleners en bevoegde interne systemen voor zover dit voor het betreffende doel nodig is. Gegevens worden niet voor advertentiedoeleinden verstrekt.',
 'retention'=>'Gegevens worden alleen bewaard zolang dit nodig is voor het betreffende doel, wettelijke verplichtingen of het instellen, uitoefenen of onderbouwen van rechtsvorderingen.',
 'rights'=>'Binnen de wettelijke voorwaarden heeft u onder meer recht op inzage, rectificatie, verwijdering, beperking, dataportabiliteit en bezwaar. U kunt ook een klacht indienen bij een toezichthoudende privacyautoriteit.',
 'security'=>'Formulieren worden via HTTPS verzonden. Toegangsgegevens en databaseverbindingen blijven uitsluitend aan de serverzijde en worden niet naar de browser gestuurd.',
],
][$lang] ?? [];

mmHeader($c['title'], $c['lead']);
?>
<section class="hero"><div><p class="eyebrow"><?= mmEscape($c['title']) ?></p><h1><?= mmEscape($c['hero']) ?></h1><p class="lead"><?= mmEscape($c['lead']) ?></p></div></section>
<section class="section">
<div class="definition"><strong><?= mmEscape($c['responsible']) ?></strong><div><?= mmEscape((string)($legal['brand'] ?? 'MysteryMarket')) ?> · <?= mmEscape((string)($legal['owner_name'] ?? '')) ?><br><?= mmEscape((string)($legal['street'] ?? '')) ?>, <?= mmEscape(trim((string)($legal['postal_code'] ?? '') . ' ' . (string)($legal['city'] ?? ''))) ?><br>privacy@mysterymarket.de</div></div>
<div class="definition"><strong><?= mmEscape($c['hosting']) ?></strong><div><?= mmEscape($text['hosting']) ?></div></div>
<div class="definition"><strong><?= mmEscape($c['contact']) ?></strong><div><?= mmEscape($text['contact']) ?></div></div>
<div class="definition"><strong><?= mmEscape($c['verify']) ?></strong><div><?= mmEscape($text['verify']) ?></div></div>
<div class="definition" id="cookies"><strong><?= mmEscape($c['cookies']) ?></strong><div><?= mmEscape($text['cookies']) ?></div></div>
<div class="definition"><strong><?= mmEscape($c['recipients']) ?></strong><div><?= mmEscape($text['recipients']) ?></div></div>
<div class="definition"><strong><?= mmEscape($c['retention']) ?></strong><div><?= mmEscape($text['retention']) ?></div></div>
<div class="definition"><strong><?= mmEscape($c['rights']) ?></strong><div><?= mmEscape($text['rights']) ?><br><a href="mailto:privacy@mysterymarket.de">privacy@mysterymarket.de</a></div></div>
<div class="definition"><strong><?= mmEscape($c['security']) ?></strong><div><?= mmEscape($text['security']) ?></div></div>
</section>
<?php mmFooter(); ?>
