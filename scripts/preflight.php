<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$required = [
    '.htaccess','VERSION','AI_START_HERE.md','docs/RELEASE_V1.md','docs/SECURITY_REVIEW_V1.md','docs/R1_1_RELEASE_REVIEW.md','docs/api/ATLAS_GEOGRAPHY_REFERENCE_API_V1.md','docs/coordination/README.md','docs/coordination/PRODUCT_HANDOFF.json',
    'index.php','404.php','services.php','audits.php','verify.php','verify-asset.php','verify-card.php','tools.php','elite-shopper.php','about.php','contact.php','backoffice/login.php','backoffice/index.php','backoffice/logout.php','backoffice/members.php','backoffice/member.php','backoffice/member-new.php','backoffice/feed.php','backoffice/feed-new.php','backoffice/contacts.php','backoffice/contact.php','backoffice/approvals.php','backoffice/approval.php','backoffice/approval-new.php','backoffice/invite.php','backoffice/activate.php','backoffice/profile.php','backoffice/membership-requests.php','backoffice/agencies.php','backoffice/agency.php','backoffice/agency-new.php','backoffice/system.php','backoffice/audit-log.php','backoffice/atlas-reference.php','backoffice/credentials.php',
    'legal-notice.php','privacy.php',
    'includes/site.php','includes/config.php','includes/db.php','includes/i18n.php','includes/content.php','includes/backoffice-auth.php','includes/atlas.php','includes/credentials.php',
    'lang/de.php','lang/en.php','lang/nl.php','scripts/verify-asset-bind.php','scripts/verify-record.php','scripts/data-maintenance.php','scripts/backoffice-user.php','scripts/backoffice-migrate.php','scripts/backoffice-review.php','scripts/credentials-review.php','scripts/r1.1-technical-gate.php','scripts/atlas-smoke.php','scripts/atlas-contract-inspect.php','scripts/atlas-street-inspect.php','scripts/build-web-artifact.sh','scripts/release-smoke.sh','scripts/final-review.sh',
    'public/css/style.css','public/media/hero-audit-office.svg','public/js/site.js','public/js/cookie-consent.js','public/js/verify-qr.js','public/vendor/qr-scanner/qr-scanner.min.js','public/vendor/qr-scanner/qr-scanner-worker.min.js','public/vendor/qr-scanner/LICENSE','public/vendor/qrcodejs/qrcode.min.js','public/vendor/qrcodejs/LICENSE','favicon.ico','robots.txt','sitemap.xml','database/schema.sql','database/20260830_backoffice_foundation.sql','database/20260830_backoffice_activation_tokens.sql','database/20260830_elite_membership_requests.sql','database/20260830_agencies.sql','database/20260830_elite_atlas_geography.sql','database/20260830_elite_work_profile.sql','database/20260830_credentials_foundation.sql','database/20260828_contact_notification_status.sql','database/20260828_contact_reference_code.sql','database/20260828_contact_rate_limit.sql','database/20260828_verify_rate_limit.sql','database/20260828_verify_identity_assets.sql'
];

$failures = 0;

$phpFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($iterator as $fileInfo) {
    if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
        continue;
    }
    $path = $fileInfo->getPathname();
    if (str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $phpFiles[] = $path;
}
sort($phpFiles);

foreach ($phpFiles as $phpFile) {
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($phpFile) . ' 2>&1';
    exec($command, $lintOutput, $lintCode);
    if ($lintCode !== 0) {
        fwrite(STDERR, "[FAIL] PHP lint: " . substr($phpFile, strlen($root) + 1) . "\n");
        foreach ($lintOutput as $line) {
            fwrite(STDERR, "       {$line}\n");
        }
        $failures++;
    }
    $lintOutput = [];
}
if ($failures === 0) {
    echo "[PASS] PHP lint across repository\n";
}

$version = trim((string)@file_get_contents($root . '/VERSION'));
if ($version === '1.1.0') {
    echo "[PASS] MysteryMarket release version 1.1.0\n";
} else {
    fwrite(STDERR, "[FAIL] VERSION must be 1.1.0 for the R1.1 release\n");
    $failures++;
}

$htaccess = @file_get_contents($root . '/.htaccess') ?: '';
if (str_contains($htaccess, 'Permissions-Policy "camera=(self), microphone=(), geolocation=(), payment=(), usb=()"')) {
    echo "[PASS] first-party Verify camera permission is configured\n";
} else {
    fwrite(STDERR, "[FAIL] Verify camera Permissions-Policy is missing or too restrictive\n");
    $failures++;
}

if (str_contains($htaccess, 'config|database|docs|includes|internal|lang|scripts')) {
    echo "[PASS] internal coordination web access is blocked\n";
} else {
    fwrite(STDERR, "[FAIL] docs/ or another internal directory is not blocked by .htaccess\n");
    $failures++;
}

if (str_contains($htaccess, 'Content-Security-Policy')) {
    echo "[PASS] Content-Security-Policy is configured\n";
} else {
    fwrite(STDERR, "[FAIL] Content-Security-Policy is missing\n");
    $failures++;
}

if (str_contains($htaccess, 'Cross-Origin-Opener-Policy "same-origin"')) {
    echo "[PASS] Cross-Origin-Opener-Policy is configured\n";
} else {
    fwrite(STDERR, "[FAIL] Cross-Origin-Opener-Policy is missing\n");
    $failures++;
}

$handoff = @file_get_contents($root . '/docs/coordination/PRODUCT_HANDOFF.json');
if ($handoff !== false && json_decode($handoff, true) !== null && json_last_error() === JSON_ERROR_NONE) {
    echo "[PASS] PRODUCT_HANDOFF.json is valid JSON\n";
} else {
    fwrite(STDERR, "[FAIL] PRODUCT_HANDOFF.json is missing or invalid JSON\n");
    $failures++;
}

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

    $assetDir = rtrim((string)($local['security']['verify_asset_dir'] ?? ''), '/');
    if ($assetDir !== '' && is_dir($assetDir) && !str_starts_with($assetDir, $root . '/')) {
        echo "[PASS] security.verify_asset_dir is configured outside the webroot\n";
    } else {
        fwrite(STDERR, "[WARN] security.verify_asset_dir should point to an existing directory outside the webroot\n");
    }

    $atlas = $local['atlas'] ?? [];
    $atlasBase = rtrim((string)($atlas['base_url'] ?? ''), '/');
    $atlasToken = trim((string)($atlas['token'] ?? ''));
    $atlasProduct = trim((string)($atlas['product'] ?? ''));
    if ($atlasBase === 'https://atlas.insodema.com/api/v1' && $atlasProduct === 'MYSTERYMARKET') {
        echo "[PASS] ATLAS endpoint and product identity are configured\n";
    } else {
        fwrite(STDERR, "[WARN] ATLAS endpoint/product configuration is not ready\n");
    }

    if ($atlasToken !== '' && $atlasToken !== 'REPLACE_WITH_MYSTERYMARKET_ATLAS_TOKEN') {
        echo "[PASS] MYSTERYMARKET ATLAS token is configured server-side\n";
    } else {
        fwrite(STDERR, "[WARN] MYSTERYMARKET ATLAS token is not configured yet\n");
    }
}

exit($failures === 0 ? 0 : 1);
