<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/content.php';

function mmStartSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function mmClientIpHash(): string
{
    $security = mmConfig()['security'] ?? [];
    $salt = trim((string)($security['rate_limit_salt'] ?? ''));
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));

    if ($salt === '' || $ip === '') {
        return '';
    }

    return hash_hmac('sha256', $ip, $salt);
}

function mmNav(): array
{
    return [
        '/' => mmT('nav.home', 'Home'),
        '/services.php' => mmT('nav.services', 'Leistungen'),
        '/audits.php' => mmT('nav.audits', 'Aktuelle Audits'),
        '/verify.php' => mmT('nav.verify', 'Verify'),
        '/tools.php' => mmT('nav.ops', 'OPS'),
        '/elite-shopper.php' => mmT('nav.elite', 'Elite Shopper'),
        '/about.php' => mmT('nav.about', 'About'),
        '/contact.php' => mmT('nav.contact', 'Kontakt'),
    ];
}

function mmHeader(string $title, string $description = '', string $robots = 'index,follow', ?string $htmlLang = null): void
{
    $nav = mmNav();
    $current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $lang = mmLanguage();
    $docLang = $htmlLang ?? $lang;
    $baseUrl = 'https://mysterymarket.de';
    $canonicalPath = mmLangUrl($current, $lang);
    $canonicalUrl = $baseUrl . $canonicalPath;
    $ogLocale = match ($docLang) {
        'en' => 'en_GB',
        'nl' => 'nl_NL',
        'tr' => 'tr_TR',
        'ar' => 'ar_AR',
        default => 'de_DE',
    };
    ?>
<!doctype html>
<html lang="<?= mmEscape($docLang) ?>"<?= $docLang === 'ar' ? ' dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= mmEscape($title) ?> · MysteryMarket</title>
    <meta name="description" content="<?= mmEscape($description) ?>">
    <meta name="robots" content="<?= mmEscape($robots) ?>">
    <meta name="theme-color" content="#001950">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="canonical" href="<?= mmEscape($canonicalUrl) ?>">
    <link rel="alternate" hreflang="de" href="<?= mmEscape($baseUrl . mmLangUrl($current, 'de')) ?>">
    <link rel="alternate" hreflang="en" href="<?= mmEscape($baseUrl . mmLangUrl($current, 'en')) ?>">
    <link rel="alternate" hreflang="nl" href="<?= mmEscape($baseUrl . mmLangUrl($current, 'nl')) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= mmEscape($baseUrl . mmLangUrl($current, 'de')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="MysteryMarket">
    <meta property="og:title" content="<?= mmEscape($title . ' · MysteryMarket') ?>">
    <meta property="og:description" content="<?= mmEscape($description) ?>">
    <meta property="og:url" content="<?= mmEscape($canonicalUrl) ?>">
    <meta property="og:locale" content="<?= mmEscape($ogLocale) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= mmEscape($title . ' · MysteryMarket') ?>">
    <meta name="twitter:description" content="<?= mmEscape($description) ?>">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>
<a class="skip-link" href="#main-content"><?= mmEscape(mmT('accessibility.skip', 'Zum Hauptinhalt')) ?></a>
<header class="site-header">
    <a class="brand" href="<?= mmEscape(mmLangUrl('/')) ?>" aria-label="MysteryMarket">
        <span class="brand-mark">MM</span>
        <span><strong>MysteryMarket</strong><small>Audit & Field Services</small></span>
    </a>
    <div class="header-right">
        <div class="global-language" aria-label="Language selector">
            <a href="<?= mmEscape($current) ?>?lang=de"<?= $lang === 'de' ? ' aria-current="page"' : '' ?>>DE</a>
            <a href="<?= mmEscape($current) ?>?lang=en"<?= $lang === 'en' ? ' aria-current="page"' : '' ?>>EN</a>
            <a href="<?= mmEscape($current) ?>?lang=nl"<?= $lang === 'nl' ? ' aria-current="page"' : '' ?>>NL</a>
        </div>
        <nav aria-label="Hauptnavigation">
            <?php foreach ($nav as $href => $label): ?>
                <a href="<?= mmEscape(mmLangUrl($href)) ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>><?= mmEscape($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<main id="main-content" tabindex="-1">
<?php
}

function mmFooter(): void
{
    ?>
</main>
<footer class="site-footer">
    <div class="footer-primary">
        <div class="footer-brand">
            <strong>MysteryMarket</strong>
            <p>Independent Audit & Field Services<br>Düsseldorf / Germany</p>
        </div>
        <div>
            <strong class="footer-heading"><?= mmEscape(mmT('footer.contact', 'Kontakt')) ?></strong>
            <p>
                <a href="mailto:hello@mysterymarket.de">hello@mysterymarket.de</a><br>
                <a href="mailto:agency@mysterymarket.de">agency@mysterymarket.de</a><br>
                <a href="mailto:eliteshopper@mysterymarket.de">eliteshopper@mysterymarket.de</a>
            </p>
        </div>
        <div>
            <strong class="footer-heading"><?= mmEscape(mmT('footer.navigation', 'Navigation')) ?></strong>
            <div class="footer-links">
                <a href="<?= mmEscape(mmLangUrl('/services.php')) ?>"><?= mmEscape(mmT('nav.services', 'Leistungen')) ?></a>
                <a href="<?= mmEscape(mmLangUrl('/audits.php')) ?>"><?= mmEscape(mmT('nav.audits', 'Aktuelle Audits')) ?></a>
                <a href="<?= mmEscape(mmLangUrl('/elite-shopper.php')) ?>"><?= mmEscape(mmT('nav.elite', 'Elite Shopper')) ?></a>
                <a href="<?= mmEscape(mmLangUrl('/tools.php')) ?>">OPS</a>
            </div>
        </div>
        <div>
            <strong class="footer-heading"><?= mmEscape(mmT('footer.legal', 'Rechtliches')) ?></strong>
            <div class="footer-links">
                <a href="<?= mmEscape(mmLangUrl('/legal-notice.php')) ?>"><?= mmEscape(mmT('footer.imprint', 'Impressum')) ?></a>
                <a href="<?= mmEscape(mmLangUrl('/privacy.php')) ?>"><?= mmEscape(mmT('footer.privacy', 'Datenschutz')) ?></a>
                <a class="link-button" data-cookie-settings href="<?= mmEscape(mmLangUrl('/privacy.php')) ?>#cookies"><?= mmEscape(mmT('footer.cookies', 'Cookie-Einstellungen')) ?></a>
            </div>
        </div>
    </div>

    <div class="product-strip" aria-label="<?= mmEscape(mmT('products.title','INSODEMA Products')) ?>">
        <div class="product-strip-intro">
            <span><?= mmEscape(mmT('products.eyebrow','INSODEMA Product Family')) ?></span>
            <strong><?= mmEscape(mmT('products.title','Tools built from operational work.')) ?></strong>
        </div>
        <a class="product-mini" href="<?= mmEscape(mmLangUrl('/tools.php')) ?>">
            <span class="product-code">OPS</span>
            <span><strong>Operations Suite</strong><small><?= mmEscape(mmT('products.ops','Planning and decision support for mobile field operations.')) ?></small></span>
        </a>
        <a class="product-mini" href="https://shopper-match.com" target="_blank" rel="noopener noreferrer">
            <span class="product-code">SM</span>
            <span><strong>ShopperMatch</strong><small><?= mmEscape(mmT('products.shoppermatch','Connect suitable shoppers, locations and project opportunities.')) ?></small></span>
        </a>
    </div>

    <div class="footer-bottom">
        <span>© <?= date('Y') ?> MysteryMarket</span>
        <span>INSODEMA · Research & Decision Lab</span>
    </div>
</footer>

<div class="cookie-panel" data-cookie-panel hidden role="dialog" aria-live="polite" aria-label="<?= mmEscape(mmT('cookie.title','Datenschutz-Einstellungen')) ?>">
    <div>
        <strong><?= mmEscape(mmT('cookie.title','Datenschutz-Einstellungen')) ?></strong>
        <p><?= mmEscape(mmT('cookie.text','Wir verwenden nur technisch notwendige Speicherungen für sichere Formulare und um Ihre Einstellung zu merken. Analyse- oder Marketing-Cookies sind derzeit nicht aktiviert.')) ?></p>
        <a href="<?= mmEscape(mmLangUrl('/privacy.php')) ?>"><?= mmEscape(mmT('footer.privacy','Datenschutz')) ?></a>
    </div>
    <div class="cookie-actions">
        <button type="button" data-cookie-necessary><?= mmEscape(mmT('cookie.necessary','Nur notwendige')) ?></button>
        <button type="button" data-cookie-accept><?= mmEscape(mmT('cookie.save','Auswahl speichern')) ?></button>
    </div>
</div>
<script src="/public/js/cookie-consent.js" defer></script>
</body>
</html>
<?php
}
