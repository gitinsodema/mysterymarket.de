<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$id = (int)($_GET['id'] ?? 0);
$type = (string)($_GET['type'] ?? '');

$columns = [
    'logo'=>['project_logo_asset',['image/png','image/jpeg','image/webp']],
    'document'=>['authorization_document_asset',['application/pdf']],
];
if ($id < 1 || !isset($columns[$type])) {
    http_response_code(404);
    exit;
}

[$column,$allowedMime] = $columns[$type];
$stmt = mmDb()->prepare("SELECT {$column} AS asset FROM credential_projects WHERE id = :id LIMIT 1");
$stmt->execute(['id'=>$id]);
$filename = trim((string)$stmt->fetchColumn());

if ($filename === '' || basename($filename) !== $filename) {
    http_response_code(404);
    exit;
}

$assetDir = rtrim((string)(mmConfig()['security']['verify_asset_dir'] ?? ''), '/');
$base = $assetDir !== '' ? realpath($assetDir) : false;
$file = $base !== false ? realpath($base . DIRECTORY_SEPARATOR . $filename) : false;
if ($base === false || $file === false || !is_file($file) || !is_readable($file)
    || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: '';
if (!in_array($mime, $allowedMime, true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($file));
header('Content-Disposition: inline; filename="' . ($type === 'document' ? 'legitimationsschreiben.pdf' : 'projektlogo') . '"');
readfile($file);
