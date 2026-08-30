<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

mmBackofficeRequireLogin('admin');

$id = (int)($_GET['id'] ?? 0);
$type = (string)($_GET['type'] ?? '');
$columns = [
    'photo'=>'photo_asset',
    'brand_logo'=>'brand_logo_asset',
    'agency_logo'=>'agency_logo_asset',
    'document'=>'document_asset',
];

if ($id < 1 || !isset($columns[$type])) {
    http_response_code(404);
    exit;
}

$column = $columns[$type];
$stmt = mmDb()->prepare(
    "SELECT {$column} AS asset
     FROM audit_verifications
     WHERE id = :id
       AND is_personal_verification = 1
     LIMIT 1"
);
$stmt->execute(['id'=>$id]);
$row = $stmt->fetch();

$asset = trim((string)($row['asset'] ?? ''));
if ($asset === '' || basename($asset) !== $asset) {
    http_response_code(404);
    exit;
}

$assetDir = rtrim((string)(mmConfig()['security']['verify_asset_dir'] ?? ''), '/');
$base = $assetDir !== '' ? realpath($assetDir) : false;
$file = $base !== false ? realpath($base . DIRECTORY_SEPARATOR . $asset) : false;

if ($base === false || $file === false || !is_file($file) || !is_readable($file)
    || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: '';
$allowed = $type === 'document'
    ? ['application/pdf']
    : ['image/png','image/jpeg','image/webp'];

if (!in_array($mime, $allowed, true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($file));
header('Content-Disposition: ' . ($type === 'document' ? 'inline' : 'inline') . '; filename="' . basename($asset) . '"');
readfile($file);
