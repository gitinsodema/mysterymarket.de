<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    'index.php','404.php','services.php','audits.php','verify.php','tools.php','elite-shopper.php','about.php','contact.php',
    'legal-notice.php','privacy.php',
    'includes/site.php','includes/config.php','includes/db.php','includes/i18n.php','includes/content.php',
    'lang/de.php','lang/en.php','lang/nl.php',
    'public/css/style.css','public/js/cookie-consent.js','favicon.ico','robots.txt','sitemap.xml','database/schema.sql','database/20260828_contact_notification_status.sql','database/20260828_contact_reference_code.sql','database/20260828_verify_rate_limit.sql'
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

$gitignore = @file_get_contents($root . '/.gitignore') ?: '';
if (str_contains($gitignore, 'config/local.php')) {
    echo "[PASS] config/local.php is listed in .gitignore\n";
} else {
    fwrite(STDERR, "[FAIL] config/local.php is not protected by .gitignore\n");
    $failures++;
}

$config = $root . '/config/local.php';
if (!is_file($config)) {
    echo "[WARN] config/local.php not present; copy config/local.example.php on server\n";
} else {
    echo "[PASS] config/local.php present\n";
    $local = require $config;

    $from = trim((string)($local['mail']['from_email'] ?? ''));
    if (filter_var($from, FILTER_VALIDATE_EMAIL)) {
        echo "[PASS] mail.from_email is configured\n";
    } else {
        fwrite(STDERR, "[WARN] mail.from_email is missing or invalid\n");
    }

    $salt = (string)($local['security']['rate_limit_salt'] ?? '');
    if (strlen($salt) >= 32) {
        echo "[PASS] security.rate_limit_salt is configured\n";
    } else {
        fwrite(STDERR, "[WARN] security.rate_limit_salt should contain at least 32 random characters; Verify will fall back to session-only throttling until configured\n");
    }
}

exit($failures === 0 ? 0 : 1);
