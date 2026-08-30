<?php
declare(strict_types=1);

require_once __DIR__ . '/site.php';
require_once __DIR__ . '/db.php';

function mmBackofficeStartSession(): void
{
    mmStartSecureSession();
    $_SESSION['mm_backoffice'] ??= [];
}

function mmBackofficeCsrfToken(): string
{
    mmBackofficeStartSession();
    $token = (string)($_SESSION['mm_backoffice']['csrf'] ?? '');
    if ($token === '') {
        $token = bin2hex(random_bytes(32));
        $_SESSION['mm_backoffice']['csrf'] = $token;
    }
    return $token;
}

function mmBackofficeVerifyCsrf(string $token): bool
{
    $stored = mmBackofficeCsrfToken();
    return $token !== '' && hash_equals($stored, $token);
}

function mmBackofficeUser(): ?array
{
    mmBackofficeStartSession();
    $user = $_SESSION['mm_backoffice']['user'] ?? null;
    return is_array($user) ? $user : null;
}

function mmBackofficeRequireLogin(?string $role = null): array
{
    $user = mmBackofficeUser();
    if (!$user) {
        header('Location: /backoffice/login.php', true, 302);
        exit;
    }

    if ($role !== null && ($user['role'] ?? '') !== $role) {
        http_response_code(403);
        header('Cache-Control: private, no-store, max-age=0');
        exit('Forbidden');
    }

    return $user;
}

function mmBackofficeIpHash(): string
{
    $salt = trim((string)(mmConfig()['security']['rate_limit_salt'] ?? ''));
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($salt === '' || $ip === '') {
        return '';
    }
    return hash_hmac('sha256', $ip, $salt);
}

function mmBackofficeEmailHash(string $email): string
{
    $salt = trim((string)(mmConfig()['security']['rate_limit_salt'] ?? ''));
    if ($salt === '') {
        return '';
    }
    return hash_hmac('sha256', strtolower(trim($email)), $salt);
}

function mmBackofficeLoginIsRateLimited(string $email): bool
{
    $ipHash = mmBackofficeIpHash();
    $emailHash = mmBackofficeEmailHash($email);
    if ($ipHash === '' && $emailHash === '') {
        return false;
    }

    $where = [];
    $params = [];
    if ($ipHash !== '') {
        $where[] = 'ip_hash = :ip_hash';
        $params['ip_hash'] = $ipHash;
    }
    if ($emailHash !== '') {
        $where[] = 'email_hash = :email_hash';
        $params['email_hash'] = $emailHash;
    }

    $sql = 'SELECT COUNT(*) FROM backoffice_login_rate_limits
            WHERE attempted_at > (NOW() - INTERVAL 15 MINUTE)
              AND (' . implode(' OR ', $where) . ')';

    $stmt = mmDb()->prepare($sql);
    $stmt->execute($params);
    return ((int)$stmt->fetchColumn()) >= 10;
}

function mmBackofficeRecordLoginAttempt(string $email): void
{
    $ipHash = mmBackofficeIpHash();
    $emailHash = mmBackofficeEmailHash($email);
    if ($ipHash === '') {
        return;
    }

    $pdo = mmDb();
    $pdo->exec('DELETE FROM backoffice_login_rate_limits WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    $stmt = $pdo->prepare(
        'INSERT INTO backoffice_login_rate_limits (ip_hash, email_hash, attempted_at)
         VALUES (:ip_hash, :email_hash, NOW())'
    );
    $stmt->execute([
        'ip_hash' => $ipHash,
        'email_hash' => $emailHash !== '' ? $emailHash : null,
    ]);
}

function mmBackofficeAudit(?int $actorId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
{
    $stmt = mmDb()->prepare(
        'INSERT INTO backoffice_audit_log
         (actor_user_id, action_key, entity_type, entity_id, metadata_json, ip_hash, created_at)
         VALUES (:actor, :action, :entity_type, :entity_id, :metadata, :ip_hash, NOW())'
    );
    $stmt->execute([
        'actor' => $actorId,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'ip_hash' => mmBackofficeIpHash() ?: null,
    ]);
}

function mmBackofficeAttemptLogin(string $email, string $password): bool
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        return false;
    }

    if (mmBackofficeLoginIsRateLimited($email)) {
        http_response_code(429);
        return false;
    }

    mmBackofficeRecordLoginAttempt($email);

    $stmt = mmDb()->prepare(
        'SELECT id, email, password_hash, role, account_status
         FROM backoffice_users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    if (!$row || ($row['account_status'] ?? '') !== 'active' || !password_verify($password, (string)$row['password_hash'])) {
        return false;
    }

    mmBackofficeStartSession();
    session_regenerate_id(true);
    $_SESSION['mm_backoffice']['user'] = [
        'id' => (int)$row['id'],
        'email' => (string)$row['email'],
        'role' => (string)$row['role'],
    ];
    $_SESSION['mm_backoffice']['csrf'] = bin2hex(random_bytes(32));

    $update = mmDb()->prepare('UPDATE backoffice_users SET last_login_at = NOW() WHERE id = :id');
    $update->execute(['id' => (int)$row['id']]);
    mmBackofficeAudit((int)$row['id'], 'login.success', 'backoffice_user', (int)$row['id']);

    return true;
}

function mmBackofficeLogout(): void
{
    $user = mmBackofficeUser();
    if ($user) {
        try {
            mmBackofficeAudit((int)$user['id'], 'logout', 'backoffice_user', (int)$user['id']);
        } catch (Throwable $e) {
            // Logout must continue even if audit storage is temporarily unavailable.
        }
    }

    $_SESSION['mm_backoffice'] = [];
    session_regenerate_id(true);
}
