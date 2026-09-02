<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only
");
    exit(1);
}

$files = [
    dirname(__DIR__) . '/database/20260830_backoffice_foundation.sql',
    dirname(__DIR__) . '/database/20260830_backoffice_activation_tokens.sql',
    dirname(__DIR__) . '/database/20260830_elite_membership_requests.sql',
    dirname(__DIR__) . '/database/20260830_agencies.sql',
    dirname(__DIR__) . '/database/20260830_elite_atlas_geography.sql',
    dirname(__DIR__) . '/database/20260830_elite_work_profile.sql',
    dirname(__DIR__) . '/database/20260830_credentials_foundation.sql',
    dirname(__DIR__) . '/database/20260830_verify_credential_revision.sql',
    dirname(__DIR__) . '/database/20260831_credential_access_binding.sql',
    dirname(__DIR__) . '/database/20260902_contact_moderation.sql',
    dirname(__DIR__) . '/database/20260902_contact_status_workflow.sql',
    dirname(__DIR__) . '/database/20260902_credential_controlled_master_data.sql',
];

$pdo = mmDb();

foreach ($files as $file) {
    $sql = @file_get_contents($file);
    if (!is_string($sql) || trim($sql) === '') {
        fwrite(STDERR, "Backoffice migration is missing or empty: {$file}\n");
        exit(1);
    }

    try {
        $pdo->exec($sql);
        echo "[PASS] migration " . basename($file) . "\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "Backoffice migration failed (" . basename($file) . "): " . $e->getMessage() . "\n");
        exit(1);
    }
}

$required = [
    'backoffice_users',
    'elite_members',
    'elite_feed_posts',
    'agency_approvals',
    'backoffice_audit_log',
    'backoffice_login_rate_limits',
    'backoffice_activation_tokens',
    'elite_membership_requests',
    'agencies',
    'verify_credential_outputs',
    'credential_roles',
    'credential_projects',
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

$columnCheck = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = :table_name
       AND column_name = :column_name'
);

foreach (['moderation_decision','moderation_reviewed_at','moderation_reviewed_by'] as $column) {
    $columnCheck->execute([
        'table_name' => 'contact_requests',
        'column_name' => $column,
    ]);
    if ((int)$columnCheck->fetchColumn() !== 1) {
        fwrite(STDERR, "[FAIL] Missing contact moderation column: {$column}\n");
        exit(1);
    }
    echo "[PASS] contact_requests.{$column}\n";
}

foreach (['credential_role_id','agency_id','credential_project_id','photo_allowed'] as $column) {
    $columnCheck->execute([
        'table_name' => 'audit_verifications',
        'column_name' => $column,
    ]);
    if ((int)$columnCheck->fetchColumn() !== 1) {
        fwrite(STDERR, "[FAIL] Missing credential control column: {$column}\n");
        exit(1);
    }
    echo "[PASS] audit_verifications.{$column}\n";
}

$columnCheck->execute([
    'table_name' => 'credential_projects',
    'column_name' => 'photo_allowed',
]);
if ((int)$columnCheck->fetchColumn() !== 1) {
    fwrite(STDERR, "[FAIL] Missing credential_projects.photo_allowed\n");
    exit(1);
}
echo "[PASS] credential_projects.photo_allowed\n";

echo "MYSTERYMARKET_BACKOFFICE_FOUNDATION_OK
";
