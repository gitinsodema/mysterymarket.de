<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$legal = mmLegal();
mmHeader('Impressum', 'Anbieterkennzeichnung für MysteryMarket.');
?>
<section class="hero"><div><p class="eyebrow">Impressum</p><h1>Anbieterinformationen.</h1><p class="lead">Informationen gemäß § 5 DDG.</p></div></section>
<section class="section">
<div class="definition"><strong>Anbieter</strong><div><strong><?= mmEscape((string)($legal['brand'] ?? 'MysteryMarket')) ?></strong><br><?= mmEscape((string)($legal['legal_form'] ?? 'Einzelunternehmen')) ?><br>Inhaber und vertretungsberechtigt: <?= mmEscape((string)($legal['owner_name'] ?? '')) ?></div></div>
<div class="definition"><strong>Anschrift</strong><div><?= mmEscape((string)($legal['street'] ?? '')) ?><br><?= mmEscape(trim((string)($legal['postal_code'] ?? '') . ' ' . (string)($legal['city'] ?? ''))) ?><br><?= mmEscape((string)($legal['country'] ?? 'Deutschland')) ?></div></div>
<div class="definition"><strong>Kontakt</strong><div>E-Mail: <a href="mailto:<?= mmEscape((string)($legal['email'] ?? 'robert.breuss@mysterymarket.de')) ?>"><?= mmEscape((string)($legal['email'] ?? 'robert.breuss@mysterymarket.de')) ?></a><br>Telefon: <?= mmEscape((string)($legal['phone'] ?? '')) ?></div></div>
<?php if (!empty($legal['vat_id'])): ?><div class="definition"><strong>USt-IdNr.</strong><div><?= mmEscape((string)$legal['vat_id']) ?></div></div><?php endif; ?>
<?php if (($legal['small_business_regulation'] ?? false) === true): ?><div class="definition"><strong>Umsatzsteuer</strong><div>Anwendung der Kleinunternehmerregelung gemäß § 19 UStG.</div></div><?php endif; ?>
</section>
<?php mmFooter(); ?>
