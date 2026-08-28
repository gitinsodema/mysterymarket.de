<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function usage(): never
{
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php scripts/verify-asset-bind.php status <reference>\n");
    fwrite(STDERR, "  php scripts/verify-asset-bind.php bind <reference> <photo|brand_logo|agency_logo|document> <filename> [document label]\n");
    fwrite(STDERR, "  php scripts/verify-asset-bind.php unbind <reference> <photo|brand_logo|agency_logo|document>\n");
    exit(2);
}

$args = $_SERVER['argv'] ?? [];
$action = strtolower(trim((string)($args[1] ?? '')));
$reference = strtoupper(trim((string)($args[2] ?? '')));

if (!in_array($action, ['status','bind','unbind'], true) || !preg_match('/^[A-Z0-9-]{4,64}$/', $reference)) {
    usage();
}

$assetDir = rtrim((string)(mmConfig()['security']['verify_asset_dir'] ?? ''), '/');
if ($assetDir === '' || !is_dir($assetDir) || !is_readable($assetDir)) {
    fwrite(STDERR, "[FAIL] Verify asset directory is missing or unreadable.\n");
    exit(1);
}

$pdo = mmDb();
$exists = $pdo->prepare('SELECT 1 FROM audit_verifications WHERE reference_code = :reference LIMIT 1');
$exists->execute(['reference' => $reference]);
if (!$exists->fetchColumn()) {
    fwrite(STDERR, "[FAIL] Unknown Verify reference: {$reference}\n");
    exit(1);
}

if ($action === 'status') {
    $stmt = $pdo->prepare(
        'SELECT reference_code, person_name, photo_asset, brand_logo_asset, agency_logo_asset,
                document_asset, document_label, document_enabled, print_card_enabled,
                is_personal_verification, is_active, valid_until
         FROM audit_verifications
         WHERE reference_code = :reference'
    );
    $stmt->execute(['reference' => $reference]);
    $row = $stmt->fetch();

    foreach ($row ?: [] as $key => $value) {
        echo $key . ': ' . ($value === null || $value === '' ? '—' : (string)$value) . PHP_EOL;
    }
    exit(0);
}

$type = strtolower(trim((string)($args[3] ?? '')));
$columns = [
    'photo' => 'photo_asset',
    'brand_logo' => 'brand_logo_asset',
    'agency_logo' => 'agency_logo_asset',
    'document' => 'document_asset',
];

if (!isset($columns[$type])) {
    usage();
}

$column = $columns[$type];

if ($action === 'unbind') {
    $sql = "UPDATE audit_verifications SET {$column} = NULL";
    if ($type === 'document') {
        $sql .= ', document_enabled = 0, document_label = NULL';
    }
    $sql .= ' WHERE reference_code = :reference';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['reference' => $reference]);

    echo "[PASS] {$type} unbound from {$reference}\n";
    exit(0);
}

$filename = trim((string)($args[4] ?? ''));
if ($filename === '' || basename($filename) !== $filename) {
    fwrite(STDERR, "[FAIL] Filename must be a plain basename without directories.\n");
    exit(1);
}

$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$allowedImages = ['png','jpg','jpeg','webp'];

if ($type === 'document') {
    if ($extension !== 'pdf') {
        fwrite(STDERR, "[FAIL] Documents must be PDF files.\n");
        exit(1);
    }
} elseif (!in_array($extension, $allowedImages, true)) {
    fwrite(STDERR, "[FAIL] Images must be PNG, JPG/JPEG or WebP. SVG is intentionally not accepted.\n");
    exit(1);
}

$file = realpath($assetDir . '/' . $filename);
$base = realpath($assetDir);
if ($base === false || $file === false || !is_file($file) || !is_readable($file) || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
    fwrite(STDERR, "[FAIL] Asset does not exist or is not readable inside the configured private Verify directory.\n");
    exit(1);
}

$size = filesize($file);
$maxBytes = $type === 'document' ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
if ($size === false || $size > $maxBytes) {
    fwrite(STDERR, "[FAIL] Asset exceeds the allowed size limit.\n");
    exit(1);
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: '';
$allowedMime = $type === 'document'
    ? ['application/pdf']
    : ['image/png','image/jpeg','image/webp'];

if (!in_array($mime, $allowedMime, true)) {
    fwrite(STDERR, "[FAIL] File content does not match an allowed MIME type: {$mime}\n");
    exit(1);
}

$sql = "UPDATE audit_verifications SET {$column} = :filename";
$params = ['filename' => $filename, 'reference' => $reference];

if ($type === 'document') {
    $label = trim((string)($args[5] ?? 'Offizielles Legitimationsschreiben'));
    if ($label === '' || mb_strlen($label) > 200) {
        fwrite(STDERR, "[FAIL] Document label must contain 1-200 characters.\n");
        exit(1);
    }
    $sql .= ', document_enabled = 1, document_label = :label';
    $params['label'] = $label;
}

$sql .= ' WHERE reference_code = :reference';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo "[PASS] {$type} bound to {$reference}: {$filename}\n";
