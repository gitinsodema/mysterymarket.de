<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/credentials.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$pdo = mmDb();
$failures = 0;

function passCredential(string $message): void { echo "[PASS] {$message}\n"; }
function failCredential(string $message): void {
    global $failures;
    $failures++;
    fwrite(STDERR, "[FAIL] {$message}\n");
}

$check = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = :table_name'
);
$check->execute(['table_name'=>'verify_credential_outputs']);
(int)$check->fetchColumn() === 1
    ? passCredential('verify_credential_outputs exists')
    : failCredential('verify_credential_outputs missing');

if ($failures === 0) {
    $personal = (int)$pdo->query(
        'SELECT COUNT(*) FROM audit_verifications WHERE is_personal_verification = 1'
    )->fetchColumn();
    $activePersonal = (int)$pdo->query(
        'SELECT COUNT(*) FROM audit_verifications WHERE is_personal_verification = 1 AND is_active = 1'
    )->fetchColumn();
    $orphanOutputs = (int)$pdo->query(
        'SELECT COUNT(*)
         FROM verify_credential_outputs o
         LEFT JOIN audit_verifications v ON v.id = o.audit_verification_id
         WHERE v.id IS NULL'
    )->fetchColumn();

    $orphanOutputs === 0
        ? passCredential('all output requests reference Verify credentials')
        : failCredential("{$orphanOutputs} orphan output request(s)");

    $activeRows = $pdo->query(
        'SELECT *
         FROM audit_verifications
         WHERE is_personal_verification = 1
           AND is_active = 1
         ORDER BY id ASC'
    )->fetchAll();

    $activeIntegrityFailures = 0;
    foreach ($activeRows as $row) {
        $errors = mmCredentialIntegrityErrors($row);
        if ($errors !== []) {
            $activeIntegrityFailures++;
            failCredential(
                (string)$row['reference_code'] . ' active credential integrity: ' . implode('; ', $errors)
            );
        }
    }
    if ($activeIntegrityFailures === 0) {
        passCredential('all active Verify credentials pass activation integrity');
    }

    $activeSuperseded = (int)$pdo->query(
        'SELECT COUNT(*)
         FROM audit_verifications current
         JOIN audit_verifications previous
           ON previous.id = current.supersedes_verification_id
         WHERE current.is_personal_verification = 1
           AND previous.is_personal_verification = 1
           AND current.is_active = 1
           AND previous.is_active = 1'
    )->fetchColumn();

    $activeSuperseded === 0
        ? passCredential('no active revision leaves its superseded credential active')
        : failCredential("{$activeSuperseded} active revision(s) still have an active superseded credential");

    echo "[INFO] personal_verify_credentials={$personal}\n";
    echo "[INFO] active_personal_verify_credentials={$activePersonal}\n";
    echo "[INFO] credential_output_requests=" . (int)$pdo->query('SELECT COUNT(*) FROM verify_credential_outputs')->fetchColumn() . "\n";
}

if ($failures === 0) {
    echo "MYSTERYMARKET_VERIFY_CREDENTIAL_SERVICE_OK\n";
    exit(0);
}

fwrite(STDERR, "MYSTERYMARKET_VERIFY_CREDENTIAL_SERVICE_FAILED failures={$failures}\n");
exit(1);
