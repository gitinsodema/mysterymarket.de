<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$legal = mmLegal();
mmHeader('Datenschutz', 'Datenschutzhinweise für mysterymarket.de.');
?>
<section class="hero"><div><p class="eyebrow">Datenschutz</p><h1>Datenschutzhinweise.</h1><p class="lead">Diese Hinweise beschreiben die Verarbeitung personenbezogener Daten auf mysterymarket.de.</p></div></section>
<section class="section">
<div class="definition"><strong>1. Verantwortlicher</strong><div><?= mmEscape((string)($legal['brand'] ?? 'MysteryMarket')) ?> · <?= mmEscape((string)($legal['owner_name'] ?? '')) ?><br><?= mmEscape((string)($legal['street'] ?? '')) ?>, <?= mmEscape(trim((string)($legal['postal_code'] ?? '') . ' ' . (string)($legal['city'] ?? ''))) ?><br>Datenschutzkontakt: <a href="mailto:privacy@mysterymarket.de">privacy@mysterymarket.de</a></div></div>
<div class="definition"><strong>2. Hosting & Server-Logs</strong><div>Beim Aufruf der Website können technisch erforderliche Verbindungs- und Protokolldaten verarbeitet werden, insbesondere IP-Adresse, Zeitpunkt, angeforderte Ressource, Statuscode, Referrer sowie Browser- und Geräteinformationen. Zweck sind Auslieferung, Fehleranalyse und Schutz der Systeme. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.</div></div>
<div class="definition"><strong>3. Kontaktanfragen</strong><div>Das Kontaktformular verarbeitet Anfrageart, Name, Organisation, E-Mail, optionale Telefonnummer, Betreff und Nachricht. Die Daten werden ausschließlich zur Bearbeitung der jeweiligen Anfrage gespeichert. Allgemeine Anfragen werden über <a href="mailto:hello@mysterymarket.de">hello@mysterymarket.de</a> bearbeitet. Rechtsgrundlage ist je nach Anfrage Art. 6 Abs. 1 lit. b oder f DSGVO.</div></div>
<div class="definition"><strong>4. Audit Verification</strong><div>Bei der Prüfung einer Referenz wird der eingegebene Verifikationscode verarbeitet, um den Status eines Auditvorgangs anzuzeigen. Öffentliche Ergebnisse enthalten nur für die Veröffentlichung freigegebene Informationen. Vertrauliche Auftraggeber- oder Projektdaten werden nicht offengelegt.</div></div>
<div class="definition"><strong>5. Cookies & lokale Speicherung</strong><div>Die Website verwendet keine Marketing- oder Audience-Analytics-Cookies. Für sichere Formulare können technisch notwendige Sessions verwendet werden. Die Entscheidung im Cookie-Hinweis wird lokal im Browser gespeichert.</div></div>
<div class="definition"><strong>6. Empfänger</strong><div>Personenbezogene Daten werden nur an Hosting- oder Systemdienstleister sowie berechtigte interne Systeme übermittelt, soweit dies für den jeweiligen Zweck erforderlich ist. Eine Weitergabe zu Werbezwecken findet nicht statt.</div></div>
<div class="definition"><strong>7. Speicherdauer</strong><div>Daten werden nur so lange gespeichert, wie dies für den jeweiligen Zweck, gesetzliche Pflichten oder die Abwehr bzw. Durchsetzung von Ansprüchen erforderlich ist.</div></div>
<div class="definition"><strong>8. Ihre Rechte</strong><div>Sie haben im Rahmen der gesetzlichen Voraussetzungen insbesondere Rechte auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch. Außerdem besteht ein Beschwerderecht bei einer Datenschutzaufsichtsbehörde. Datenschutzanfragen richten Sie bitte an <a href="mailto:privacy@mysterymarket.de">privacy@mysterymarket.de</a>.</div></div>
<div class="definition"><strong>9. Sicherheit</strong><div>Formulare werden über HTTPS übertragen. Zugangsdaten und Datenbankverbindungen liegen ausschließlich serverseitig und werden nicht an den Browser ausgeliefert.</div></div>
</section>
<?php mmFooter(); ?>
