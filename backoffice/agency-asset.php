<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$id = (int)($_GET['id'] ?? 0);
$type = (string)($_GET['type'] ?? '');

if ($id < 1 || $type !== 'logo') {
    http_response_code(404);
    exit;
}

$stmt = mmDb()->prepare(
    'SELECT a.logo_asset, a.elite_visible
     FROM agencies a
     WHERE a.id = :id
     LIMIT 1'
);
$stmt->execute(['id'=>$id]);
$row = $stmt->fetch();

if (!$row || trim((string)($row['logo_asset'] ?? '')) === '') {
    http_response_code(404);
    exit;
}

if (($user['role'] ?? '') !== 'admin') {
    $memberStmt = mmDb()->prepare(
        "SELECT COUNT(*)
         FROM elite_members
         WHERE user_id = :user_id
           AND membership_status = 'active'"
    );
    $memberStmt->execute(['user_id'=>(int)$user['id']]);
    if ((int)$memberStmt->fetchColumn() !== 1 || (int)$row['elite_visible'] !== 1) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$filename = trim((string)$row['logo_asset']);
if (basename($filename) !== $filename) {
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
if (!in_array($mime, ['image/png','image/jpeg','image/webp'], true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($file));
header('Content-Disposition: inline; filename="agentur-logo"');
readfile($file);
