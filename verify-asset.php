<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/site.php';
require_once __DIR__ . '/includes/db.php';

mmStartSecureSession();

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
$type = trim((string)($_GET['type'] ?? ''));

if (!preg_match('/^[A-Z0-9-]{4,64}$/', $code) || !in_array($type, ['photo','brand_logo','agency_logo','document'], true)) {
    http_response_code(404);
    exit;
}

$verifiedAt = (int)($_SESSION['mm_verified_records'][$code] ?? 0);
if ($verifiedAt < time() - 900) {
    http_response_code(403);
    exit;
}

$column = match ($type) {
    'photo' => 'photo_asset',
    'brand_logo' => 'brand_logo_asset',
    'agency_logo' => 'agency_logo_asset',
    'document' => 'document_asset',
};

try {
    $stmt = mmDb()->prepare(
        "SELECT {$column} AS asset, document_enabled
         FROM audit_verifications
         WHERE reference_code = :code
           AND is_active = 1
           AND (valid_from IS NULL OR valid_from <= CURRENT_DATE())
           AND (valid_until IS NULL OR valid_until >= CURRENT_DATE())
         LIMIT 1"
    );
    $stmt->execute(['code' => $code]);
    $row = $stmt->fetch();
} catch (Throwable $e) {
    http_response_code(503);
    exit;
}

if (!$row || empty($row['asset']) || ($type === 'document' && empty($row['document_enabled']))) {
    http_response_code(404);
    exit;
}

$asset = trim((string)$row['asset']);
if ($asset === '' || basename($asset) !== $asset) {
    http_response_code(404);
    exit;
}

$assetDir = rtrim((string)(mmConfig()['security']['verify_asset_dir'] ?? ''), '/');
if ($assetDir === '' || !is_dir($assetDir)) {
    http_response_code(503);
    exit;
}

$base = realpath($assetDir);
$file = realpath($assetDir . '/' . $asset);
if ($base === false || $file === false || !is_file($file) || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = match ($extension) {
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg', 'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    default => '',
};

if ($mime === '' || ($type === 'document' && $extension !== 'pdf') || ($type !== 'document' && !in_array($extension, ['png','jpg','jpeg','webp'], true))) {
    http_response_code(415);
    exit;
}

$maxBytes = $type === 'document' ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
$fileSize = filesize($file);
if ($fileSize === false || $fileSize > $maxBytes) {
    http_response_code(413);
    exit;
}

$detectedMime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: '';
$allowedMime = $type === 'document'
    ? ['application/pdf']
    : ['image/png','image/jpeg','image/webp'];

if (!in_array($detectedMime, $allowedMime, true)) {
    http_response_code(415);
    exit;
}

$mime = $detectedMime;

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($file));
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

if ($type === 'document') {
    header('Content-Disposition: inline; filename="MysteryMarket-Legitimation.pdf"');
}

readfile($file);
