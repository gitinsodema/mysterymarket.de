<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/atlas.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

if (!mmAtlasIsConfigured()) {
    fwrite(STDERR, "[FAIL] ATLAS is not configured in config/local.php\n");
    exit(1);
}

try {
    $response = mmAtlasCountries();
    $count = is_array($response['data'] ?? null) ? count($response['data']) : 0;
    echo "[PASS] ATLAS authentication and country lookup\n";
    echo "[INFO] countries={$count}\n";
    echo "MYSTERYMARKET_ATLAS_SMOKE_OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[FAIL] " . $e->getMessage() . "\n");
    exit(1);
}
