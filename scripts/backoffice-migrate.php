<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only
");
    exit(1);
}

$file = dirname(__DIR__) . '/database/20260830_backoffice_foundation.sql';
$sql = @file_get_contents($file);
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Backoffice foundation migration is missing or empty.
");
    exit(1);
}

$pdo = mmDb();

try {
    $pdo->exec($sql);
} catch (Throwable $e) {
    fwrite(STDERR, "Backoffice foundation migration failed: " . $e->getMessage() . "
");
    exit(1);
}

$required = [
    'backoffice_users',
    'elite_members',
    'elite_feed_posts',
    'agency_approvals',
    'backoffice_audit_log',
    'backoffice_login_rate_limits',
];

$check = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = :table_name'
);

foreach ($required as $table) {
    $check->execute(['table_name' => $table]);
    if ((int)$check->fetchColumn() !== 1) {
        fwrite(STDERR, "[FAIL] Missing table after migration: {$table}
");
        exit(1);
    }
    echo "[PASS] {$table}
";
}

echo "MYSTERYMARKET_BACKOFFICE_FOUNDATION_OK
";
