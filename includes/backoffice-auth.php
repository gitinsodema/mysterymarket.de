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

    $stmt = mmDb()->prepare(
        'SELECT id, email, role, account_status
         FROM backoffice_users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => (int)($user['id'] ?? 0)]);
    $current = $stmt->fetch();

    if (!$current || ($current['account_status'] ?? '') !== 'active') {
        $_SESSION['mm_backoffice'] = [];
        session_regenerate_id(true);
        header('Location: /backoffice/login.php', true, 302);
        exit;
    }

    $user = [
        'id' => (int)$current['id'],
        'email' => (string)$current['email'],
        'role' => (string)$current['role'],
    ];
    $_SESSION['mm_backoffice']['user'] = $user;

    if ($role !== null && ($user['role'] ?? '') !== $role) {
        http_response_code(403);
        header('Cache-Control: private, no-store, max-age=0');
        exit('Forbidden');
    }

    return $user;
}


function mmBackofficeCanAccessCredential(array $user, ?int $credentialSubjectId): bool
{
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }
    if (($user['role'] ?? '') !== 'elite' || !$credentialSubjectId) {
        return false;
    }

    $stmt = mmDb()->prepare(
        'SELECT COUNT(*) FROM credential_subjects
         WHERE id = :subject_id AND backoffice_user_id = :user_id'
    );
    $stmt->execute([
        'subject_id' => $credentialSubjectId,
        'user_id' => (int)($user['id'] ?? 0),
    ]);
    return (int)$stmt->fetchColumn() === 1;
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


function mmBackofficeCreateActivationToken(int $userId, int $createdBy, int $hours = 48): string
{
    $plain = bin2hex(random_bytes(32));
    $hash = hash('sha256', $plain);

    $pdo = mmDb();
    $revoke = $pdo->prepare(
        'UPDATE backoffice_activation_tokens
         SET used_at = NOW()
         WHERE user_id = :user_id AND used_at IS NULL'
    );
    $revoke->execute(['user_id' => $userId]);

    $stmt = $pdo->prepare(
        'INSERT INTO backoffice_activation_tokens
         (user_id, token_hash, expires_at, used_at, created_by, created_at)
         VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 48 HOUR), NULL, :created_by, NOW())'
    );
    $stmt->execute([
        'user_id' => $userId,
        'token_hash' => $hash,
        'created_by' => $createdBy,
    ]);

    mmBackofficeAudit($createdBy, 'elite_invitation.created', 'backoffice_user', $userId, ['expires_in_hours' => 48]);
    return $plain;
}

function mmBackofficeActivationRecord(string $plainToken): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
        return null;
    }

    $stmt = mmDb()->prepare(
        'SELECT t.id AS token_id, t.user_id, t.expires_at, t.used_at,
                u.email, u.role, u.account_status,
                m.id AS member_id, m.member_code, m.display_name, m.membership_status
         FROM backoffice_activation_tokens t
         JOIN backoffice_users u ON u.id = t.user_id
         LEFT JOIN elite_members m ON m.user_id = u.id
         WHERE t.token_hash = :token_hash
         LIMIT 1'
    );
    $stmt->execute(['token_hash' => hash('sha256', $plainToken)]);
    $row = $stmt->fetch();

    if (!$row || ($row['role'] ?? '') !== 'elite' || $row['used_at'] !== null) {
        return null;
    }

    $expires = strtotime((string)$row['expires_at']);
    if ($expires === false || $expires < time()) {
        return null;
    }

    return $row;
}

function mmBackofficeActivateElite(string $plainToken, string $password): bool
{
    $record = mmBackofficeActivationRecord($plainToken);
    if (!$record || strlen($password) < 12) {
        return false;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($passwordHash) || $passwordHash === '') {
        return false;
    }

    $pdo = mmDb();
    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare(
            'UPDATE backoffice_users
             SET password_hash = :password_hash,
                 account_status = \'active\',
                 updated_at = NOW()
             WHERE id = :id AND role = \'elite\''
        );
        $userStmt->execute([
            'password_hash' => $passwordHash,
            'id' => (int)$record['user_id'],
        ]);

        $memberStmt = $pdo->prepare(
            'UPDATE elite_members
             SET membership_status = \'active\',
                 joined_at = COALESCE(joined_at, NOW()),
                 updated_at = NOW()
             WHERE user_id = :user_id'
        );
        $memberStmt->execute(['user_id' => (int)$record['user_id']]);

        $tokenStmt = $pdo->prepare(
            'UPDATE backoffice_activation_tokens
             SET used_at = NOW()
             WHERE id = :token_id AND used_at IS NULL'
        );
        $tokenStmt->execute(['token_id' => (int)$record['token_id']]);

        if ($tokenStmt->rowCount() !== 1) {
            throw new RuntimeException('Activation token was already used.');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }

    mmBackofficeAudit((int)$record['user_id'], 'elite_invitation.activated', 'elite_member', (int)$record['member_id']);
    return true;
}


function mmBackofficeStatusTone(string $status): string
{
    return match (strtolower(trim($status))) {
        'active', 'approved', 'done', 'success', 'valid', 'completed' => 'ok',
        'new', 'draft', 'requested', 'pending', 'pending_review', 'invited', 'paused', 'open' => 'warn',
        'rejected', 'suspended', 'ended', 'expired', 'failed', 'disabled' => 'danger',
        'seen', 'cancelled' => 'info',
        default => 'neutral',
    };
}

function mmBackofficeStatusBadge(string $status, ?string $label = null): string
{
    $text = $label ?? $status;
    return '<span class="status status--' . mmEscape(mmBackofficeStatusTone($status)) . '">'
        . mmEscape($text) . '</span>';
}
