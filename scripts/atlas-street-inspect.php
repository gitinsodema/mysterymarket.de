<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/atlas.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

try {
    $localities = mmAtlasLocalities('DE', '50667', 'Kö', 5);
    $items = $localities['data'] ?? [];
    if (!is_array($items) || !isset($items[0]) || !is_array($items[0])) {
        throw new RuntimeException('No Köln locality returned.');
    }

    $localityId = trim((string)($items[0]['atlas_id'] ?? ''));
    if ($localityId === '') {
        throw new RuntimeException('Locality ATLAS ID missing.');
    }

    $streets = mmAtlasStreets('DE', '50667', $localityId, 'Ho', 5);
    $streetItems = $streets['data'] ?? [];
    $first = is_array($streetItems) && isset($streetItems[0]) && is_array($streetItems[0])
        ? $streetItems[0]
        : null;

    echo json_encode([
        'locality_id' => $localityId,
        'first_street' => $first,
        'street_keys' => is_array($first) ? array_keys($first) : [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

    echo "MYSTERYMARKET_ATLAS_STREET_INSPECT_OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[FAIL] " . $e->getMessage() . "\n");
    exit(1);
}
