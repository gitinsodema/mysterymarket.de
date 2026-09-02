<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin();
$memberId = (int)($_GET['member_id'] ?? 0);

if ($memberId < 1) {
    if (($user['role'] ?? '') !== 'elite') {
        http_response_code(404);
        exit;
    }
    $stmt = mmDb()->prepare('SELECT id FROM elite_members WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id'=>(int)$user['id']]);
    $memberId = (int)$stmt->fetchColumn();
}

$stmt = mmDb()->prepare(
    'SELECT id, user_id, profile_photo_asset
     FROM elite_members
     WHERE id = :id
     LIMIT 1'
);
$stmt->execute(['id'=>$memberId]);
$member = $stmt->fetch();

if (!$member || trim((string)($member['profile_photo_asset'] ?? '')) === '') {
    http_response_code(404);
    exit;
}

if (($user['role'] ?? '') !== 'admin' && (int)$member['user_id'] !== (int)$user['id']) {
    http_response_code(403);
    exit('Forbidden');
}

$filename = trim((string)$member['profile_photo_asset']);
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
header('Content-Disposition: inline; filename="elite-profile-photo"');
readfile($file);
