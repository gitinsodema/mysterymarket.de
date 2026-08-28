<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'index.php','404.php','services.php','audits.php','verify.php','tools.php','elite-shopper.php','about.php','contact.php',
    'legal-notice.php','privacy.php',
    'includes/site.php','includes/config.php','includes/db.php','includes/i18n.php','includes/content.php',
    'lang/de.php','lang/en.php','lang/nl.php',
    'public/css/style.css','public/js/cookie-consent.js','favicon.ico','robots.txt','sitemap.xml','database/schema.sql','database/20260828_contact_notification_status.sql','database/20260828_contact_reference_code.sql'
];

$failures = 0;
foreach ($required as $file) {
    if (!is_file($root . '/' . $file)) {
        fwrite(STDERR, "[FAIL] Missing: {$file}\n");
        $failures++;
    } else {
        echo "[PASS] {$file}\n";
    }
}

if (PHP_VERSION_ID < 80500) {
    fwrite(STDERR, "[FAIL] PHP 8.5+ required. Current: " . PHP_VERSION . "\n");
    $failures++;
}

$config = $root . '/config/local.php';
echo is_file($config)
    ? "[PASS] config/local.php present\n"
    : "[WARN] config/local.php not present; copy config/local.example.php on server\n";

exit($failures === 0 ? 0 : 1);
