<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$readiness = mmAppleWalletReadiness();

if (!empty($readiness['ready'])) {
    echo "[PASS] Apple Wallet configuration is ready.\n";
    echo "MYSTERYMARKET_APPLE_WALLET_READY\n";
    exit(0);
}

foreach ($readiness['issues'] as $issue) {
    echo "[INFO] {$issue}\n";
}
echo "MYSTERYMARKET_APPLE_WALLET_NOT_READY\n";
exit(0);
