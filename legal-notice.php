<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/site.php';
$legal = mmLegal();
$c = mmPageCopy('legal');
mmHeader($c['title'], $c['lead']);
?>
<section class="hero"><div><p class="eyebrow"><?= mmEscape($c['title']) ?></p><h1><?= mmEscape($c['hero']) ?></h1><p class="lead"><?= mmEscape($c['lead']) ?></p></div></section>
<section class="section">
<div class="definition"><strong><?= mmEscape($c['provider']) ?></strong><div><strong><?= mmEscape((string)($legal['brand'] ?? 'MysteryMarket')) ?></strong><br><?= mmEscape((string)($legal['legal_form'] ?? 'Einzelunternehmen')) ?><br><?= mmEscape($c['owner']) ?>: <?= mmEscape((string)($legal['owner_name'] ?? '')) ?></div></div>
<div class="definition"><strong><?= mmEscape($c['address']) ?></strong><div><?= mmEscape((string)($legal['street'] ?? '')) ?><br><?= mmEscape(trim((string)($legal['postal_code'] ?? '') . ' ' . (string)($legal['city'] ?? ''))) ?><br><?= mmEscape((string)($legal['country'] ?? 'Deutschland')) ?></div></div>
<div class="definition"><strong><?= mmEscape($c['contact']) ?></strong><div>E-Mail: <a href="mailto:<?= mmEscape((string)($legal['email'] ?? 'hello@mysterymarket.de')) ?>"><?= mmEscape((string)($legal['email'] ?? 'hello@mysterymarket.de')) ?></a></div></div>
<?php if (!empty($legal['vat_id'])): ?><div class="definition"><strong><?= mmEscape($c['vat']) ?></strong><div><?= mmEscape((string)$legal['vat_id']) ?></div></div><?php endif; ?>
<?php if (($legal['small_business_regulation'] ?? false) === true): ?><div class="definition"><strong><?= mmEscape($c['tax']) ?></strong><div><?= mmEscape($c['small']) ?></div></div><?php endif; ?>
</section>
<?php mmFooter(); ?>
