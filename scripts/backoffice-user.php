<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only
");
    exit(1);
}

$email = strtolower(trim((string)($argv[1] ?? '')));
$role = strtolower(trim((string)($argv[2] ?? '')));
$password = (string)(getenv('MM_BACKOFFICE_PASSWORD') ?: '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: MM_BACKOFFICE_PASSWORD='...' php scripts/backoffice-user.php <email> <admin|elite>
");
    exit(1);
}
if (!in_array($role, ['admin','elite'], true)) {
    fwrite(STDERR, "Role must be admin or elite.
");
    exit(1);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "MM_BACKOFFICE_PASSWORD must be at least 12 characters.
");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if (!is_string($hash) || $hash === '') {
    fwrite(STDERR, "Password hashing failed.
");
    exit(1);
}

$stmt = mmDb()->prepare(
    'INSERT INTO backoffice_users (email, password_hash, role, account_status, created_at, updated_at)
     VALUES (:email, :password_hash, :role, \'active\', NOW(), NOW())
     ON DUPLICATE KEY UPDATE
       password_hash = VALUES(password_hash),
       role = VALUES(role),
       account_status = \'active\',
       updated_at = NOW()'
);
$stmt->execute([
    'email' => $email,
    'password_hash' => $hash,
    'role' => $role,
]);

echo "Backoffice user ready: {$email} ({$role})
";
