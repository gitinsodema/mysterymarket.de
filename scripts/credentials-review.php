<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

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

$required = ['credential_subjects','credentials','credential_orders'];
$check = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = :table_name'
);

foreach ($required as $table) {
    $check->execute(['table_name'=>$table]);
    (int)$check->fetchColumn() === 1
        ? passCredential("{$table} exists")
        : failCredential("{$table} missing");
}

if ($failures === 0) {
    $orphanCredentials = (int)$pdo->query(
        'SELECT COUNT(*) FROM credentials c
         LEFT JOIN credential_subjects s ON s.id = c.subject_id
         WHERE s.id IS NULL'
    )->fetchColumn();
    $orphanCredentials === 0
        ? passCredential('all credentials reference a credential subject')
        : failCredential("{$orphanCredentials} orphan credential(s)");

    $orphanOrders = (int)$pdo->query(
        'SELECT COUNT(*) FROM credential_orders o
         LEFT JOIN credentials c ON c.id = o.credential_id
         WHERE c.id IS NULL'
    )->fetchColumn();
    $orphanOrders === 0
        ? passCredential('all credential orders reference a credential')
        : failCredential("{$orphanOrders} orphan credential order(s)");

    $eliteWithoutSubject = (int)$pdo->query(
        "SELECT COUNT(*)
         FROM elite_members m
         JOIN backoffice_users u ON u.id = m.user_id
         LEFT JOIN credential_subjects s ON s.backoffice_user_id = u.id
         WHERE m.membership_status = 'active'
           AND s.id IS NULL"
    )->fetchColumn();
    $eliteWithoutSubject === 0
        ? passCredential('all active Elite members have a credential subject')
        : failCredential("{$eliteWithoutSubject} active Elite member(s) lack credential subjects");

    $subjects = (int)$pdo->query('SELECT COUNT(*) FROM credential_subjects')->fetchColumn();
    $credentials = (int)$pdo->query('SELECT COUNT(*) FROM credentials')->fetchColumn();
    $orders = (int)$pdo->query('SELECT COUNT(*) FROM credential_orders')->fetchColumn();
    echo "[INFO] credential_subjects={$subjects}\n";
    echo "[INFO] credentials={$credentials}\n";
    echo "[INFO] credential_orders={$orders}\n";
}

if ($failures === 0) {
    echo "MYSTERYMARKET_CREDENTIAL_FOUNDATION_OK\n";
    exit(0);
}

fwrite(STDERR, "MYSTERYMARKET_CREDENTIAL_FOUNDATION_FAILED failures={$failures}\n");
exit(1);
