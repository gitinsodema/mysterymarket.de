<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';
require_once dirname(__DIR__) . '/includes/atlas.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin();

if (!mmAtlasIsConfigured()) {
    http_response_code(503);
    echo json_encode(['success'=>false,'error'=>['code'=>'atlas_not_configured','message'=>'ATLAS ist noch nicht konfiguriert.']]);
    exit;
}

$action=(string)($_GET['action'] ?? '');

try {
    $response = match ($action) {
        'countries' => mmAtlasCountries(),
        'subdivisions' => mmAtlasSubdivisions((string)($_GET['country_code'] ?? '')),
        'postal_areas' => mmAtlasPostalAreas((string)($_GET['subdivision_id'] ?? '')),
        'postal_area' => mmAtlasPostalArea((string)($_GET['country_code'] ?? ''),(string)($_GET['postal_code'] ?? '')),
        'localities' => mmAtlasLocalities(
            (string)($_GET['country_code'] ?? ''),
            (string)($_GET['postal_code'] ?? ''),
            (string)($_GET['q'] ?? ''),
            (int)($_GET['limit'] ?? 20)
        ),
        'streets' => mmAtlasStreets(
            (string)($_GET['country_code'] ?? ''),
            (string)($_GET['postal_code'] ?? ''),
            (string)($_GET['locality_id'] ?? ''),
            (string)($_GET['q'] ?? ''),
            (int)($_GET['limit'] ?? 20)
        ),
        default => throw new InvalidArgumentException('Unknown ATLAS reference action.'),
    };

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>['code'=>'invalid_request','message'=>'Ungültige ATLAS-Abfrage.']]);
} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['success'=>false,'error'=>['code'=>'atlas_unavailable','message'=>'ATLAS ist derzeit nicht verfügbar.']]);
}
