<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$action = strtolower(trim((string)($_SERVER['argv'][1] ?? 'status')));
if (!in_array($action, ['status','cleanup-rate-limits'], true)) {
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php scripts/data-maintenance.php status\n");
    fwrite(STDERR, "  php scripts/data-maintenance.php cleanup-rate-limits\n");
    exit(2);
}

$pdo = mmDb();

if ($action === 'cleanup-rate-limits') {
    $pdo->beginTransaction();
    try {
        $verify = $pdo->exec("DELETE FROM verify_rate_limits WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
        $contact = $pdo->exec("DELETE FROM contact_rate_limits WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
        $pdo->commit();

        echo "[PASS] expired rate-limit data removed\n";
        echo "verify_rate_limits deleted: " . (int)$verify . PHP_EOL;
        echo "contact_rate_limits deleted: " . (int)$contact . PHP_EOL;
        exit(0);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        fwrite(STDERR, "[FAIL] rate-limit cleanup failed\n");
        exit(1);
    }
}

$queries = [
    'contact_requests' => 'SELECT COUNT(*) AS total, MIN(created_at) AS oldest, MAX(created_at) AS newest FROM contact_requests',
    'verify_rate_limits' => 'SELECT COUNT(*) AS total, MIN(attempted_at) AS oldest, MAX(attempted_at) AS newest FROM verify_rate_limits',
    'contact_rate_limits' => 'SELECT COUNT(*) AS total, MIN(attempted_at) AS oldest, MAX(attempted_at) AS newest FROM contact_rate_limits',
];

foreach ($queries as $label => $sql) {
    $row = $pdo->query($sql)->fetch() ?: [];
    echo $label
        . ': total=' . (int)($row['total'] ?? 0)
        . ' oldest=' . (($row['oldest'] ?? null) ?: '—')
        . ' newest=' . (($row['newest'] ?? null) ?: '—')
        . PHP_EOL;
}

echo "[INFO] Contact-request retention is report-only until an explicit retention policy is approved.\n";
