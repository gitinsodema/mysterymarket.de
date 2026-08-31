<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, noarchive');

$user = mmBackofficeRequireLogin('admin');
$outputId = (int)($_GET['output_id'] ?? 0);
if ($outputId < 1) {
    http_response_code(404);
    exit;
}

$stmt = mmDb()->prepare(
    "SELECT o.id AS output_id, o.output_type, o.output_status,
            v.*
     FROM verify_credential_outputs o
     JOIN audit_verifications v ON v.id = o.audit_verification_id
     WHERE o.id = :id
       AND o.output_type = 'apple_wallet'
     LIMIT 1"
);
$stmt->execute(['id'=>$outputId]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit;
}

if (!in_array((string)$row['output_status'], ['processing','ready'], true)) {
    http_response_code(409);
    echo 'Der Wallet-Ausgabeauftrag ist noch nicht zur Pass-Erzeugung freigegeben.';
    exit;
}

try {
    $file = mmAppleWalletBuildPass($row);

    mmBackofficeAudit(
        (int)$user['id'],
        'verify_credential_output.wallet_generated',
        'verify_credential_output',
        $outputId,
        [
            'reference_code'=>(string)$row['reference_code'],
            'output_status'=>(string)$row['output_status'],
        ]
    );

    $downloadName = preg_replace('/[^A-Z0-9_-]+/i', '-', (string)$row['reference_code']) . '.pkpass';

    header('Content-Type: application/vnd.apple.pkpass');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . (string)filesize($file));
    header('X-Content-Type-Options: nosniff');

    readfile($file);
    @unlink($file);
    exit;
} catch (Throwable $e) {
    http_response_code(503);
    echo 'Apple Wallet Pass konnte nicht erzeugt werden: ' . mmEscape($e->getMessage());
}
