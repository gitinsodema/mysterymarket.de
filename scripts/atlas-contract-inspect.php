<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/atlas.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

if (!mmAtlasIsConfigured()) {
    fwrite(STDERR, "[FAIL] ATLAS is not configured\n");
    exit(1);
}

function shape(array $value): array
{
    $out = [];
    foreach ($value as $key => $item) {
        if (is_array($item)) {
            $out[$key] = array_is_list($item)
                ? ['type'=>'list','count'=>count($item),'first'=>isset($item[0]) && is_array($item[0]) ? array_keys($item[0]) : null]
                : ['type'=>'object','keys'=>array_keys($item)];
        } else {
            $out[$key] = get_debug_type($item);
        }
    }
    return $out;
}

function firstDataItem(array $response): ?array
{
    $data = $response['data'] ?? null;
    if (!is_array($data)) {
        return null;
    }
    if (array_is_list($data)) {
        return isset($data[0]) && is_array($data[0]) ? $data[0] : null;
    }
    foreach ($data as $value) {
        if (is_array($value) && array_is_list($value) && isset($value[0]) && is_array($value[0])) {
            return $value[0];
        }
    }
    return $data;
}

try {
    echo "=== countries ===\n";
    $countries = mmAtlasCountries();
    echo json_encode([
        'data_shape'=>shape(is_array($countries['data'] ?? null) ? $countries['data'] : []),
        'first_item'=>firstDataItem($countries),
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n\n";

    echo "=== DE subdivisions ===\n";
    $subs = mmAtlasSubdivisions('DE');
    $subFirst = firstDataItem($subs);
    echo json_encode([
        'data_shape'=>shape(is_array($subs['data'] ?? null) ? $subs['data'] : []),
        'first_item'=>$subFirst,
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n\n";

    echo "=== DE 50667 postal area ===\n";
    $postal = mmAtlasPostalArea('DE', '50667');
    echo json_encode([
        'data_shape'=>shape(is_array($postal['data'] ?? null) ? $postal['data'] : []),
        'data'=>$postal['data'] ?? null,
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n\n";

    echo "=== DE 50667 localities q=Kö ===\n";
    $localities = mmAtlasLocalities('DE','50667','Kö',20);
    echo json_encode([
        'data_shape'=>shape(is_array($localities['data'] ?? null) ? $localities['data'] : []),
        'first_item'=>firstDataItem($localities),
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";

    echo "MYSTERYMARKET_ATLAS_CONTRACT_INSPECT_OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[FAIL] " . $e->getMessage() . "\n");
    exit(1);
}
