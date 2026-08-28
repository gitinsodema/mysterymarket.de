<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function mmNav(): array
{
    return [
        '/' => 'Home',
        '/services.php' => 'Leistungen',
        '/audits.php' => 'Aktuelle Audits',
        '/verify.php' => 'Verify',
        '/tools.php' => 'OPS',
        '/about.php' => 'About',
        '/contact.php' => 'Kontakt',
    ];
}

function mmHeader(string $title, string $description = ''): void
{
    $nav = mmNav();
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    ?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= mmEscape($title) ?> · MysteryMarket</title>
    <meta name="description" content="<?= mmEscape($description) ?>">
    <meta name="robots" content="index,follow">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/" aria-label="MysteryMarket Startseite">
        <span class="brand-mark">MM</span>
        <span><strong>MysteryMarket</strong><small>Audit & Field Services</small></span>
    </a>
    <nav aria-label="Hauptnavigation">
        <?php foreach ($nav as $href => $label): ?>
            <a href="<?= mmEscape($href) ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>><?= mmEscape($label) ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main>
<?php
}

function mmFooter(): void
{
    ?>
</main>
<footer class="site-footer">
    <div class="footer-brand">
        <strong>MysteryMarket</strong>
        <p>Independent Audit & Field Services<br>Düsseldorf / Germany</p>
    </div>
    <div>
        <strong class="footer-heading">Kontakt</strong>
        <p><a href="mailto:hello@mysterymarket.de">hello@mysterymarket.de</a><br><a href="mailto:partners@mysterymarket.de">partners@mysterymarket.de</a></p>
    </div>
    <div>
        <strong class="footer-heading">Navigation</strong>
        <div class="footer-links">
            <a href="/services.php">Leistungen</a>
            <a href="/audits.php">Aktuelle Audits</a>
            <a href="/verify.php">Verify</a>
            <a href="/tools.php">OPS</a>
        </div>
    </div>
    <div>
        <strong class="footer-heading">Rechtliches</strong>
        <div class="footer-links">
            <a href="/legal-notice.php">Impressum</a>
            <a href="/privacy.php">Datenschutz</a>
            <button type="button" class="link-button" data-cookie-settings>Cookie-Einstellungen</button>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© <?= date('Y') ?> MysteryMarket</span>
        <span>Operational tools by INSODEMA · Research & Decision Lab</span>
    </div>
</footer>

<div class="cookie-panel" data-cookie-panel hidden role="dialog" aria-live="polite" aria-label="Datenschutz-Einstellungen">
    <div>
        <strong>Datenschutz-Einstellungen</strong>
        <p>Wir verwenden nur technisch notwendige Speicherungen für sichere Formulare und um Ihre Einstellung zu merken. Analyse- oder Marketing-Cookies sind derzeit nicht aktiviert.</p>
        <a href="/privacy.php">Datenschutzhinweise</a>
    </div>
    <div class="cookie-actions">
        <button type="button" data-cookie-necessary>Nur notwendige</button>
        <button type="button" data-cookie-accept>Auswahl speichern</button>
    </div>
</div>
<script src="/public/js/cookie-consent.js" defer></script>
</body>
</html>
<?php
}
