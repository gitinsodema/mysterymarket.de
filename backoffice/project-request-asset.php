<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$id = (int)($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    exit;
}

$stmt = mmDb()->prepare(
    "SELECT r.authorization_document_asset, m.user_id
     FROM credential_project_requests r
     JOIN elite_members m ON m.id = r.member_id
     WHERE r.id = :id
     LIMIT 1"
);
$stmt->execute(['id'=>$id]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    exit;
}

if (($user['role'] ?? '') !== 'admin' && (int)$row['user_id'] !== (int)$user['id']) {
    http_response_code(403);
    exit('Forbidden');
}

$filename = trim((string)$row['authorization_document_asset']);
$assetDir = rtrim((string)(mmConfig()['security']['verify_asset_dir'] ?? ''), '/');
$base = $assetDir !== '' ? realpath($assetDir) : false;
$file = $base !== false && basename($filename) === $filename
    ? realpath($base . DIRECTORY_SEPARATOR . $filename)
    : false;

if ($base === false || $file === false || !is_file($file) || !is_readable($file)
    || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)
    || (new finfo(FILEINFO_MIME_TYPE))->file($file) !== 'application/pdf') {
    http_response_code(404);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Length: ' . (string)filesize($file));
header('Content-Disposition: inline; filename="legitimationsschreiben.pdf"');
readfile($file);
