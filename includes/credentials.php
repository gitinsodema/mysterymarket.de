<?php
declare(strict_types=1);

require_once __DIR__ . '/backoffice-auth.php';

function mmCredentialSubjectForUser(array $user, bool $create = true): ?array
{
    $userId = (int)($user['id'] ?? 0);
    if ($userId < 1) {
        return null;
    }

    $stmt = mmDb()->prepare(
        'SELECT id, backoffice_user_id, display_name, subject_status, created_at, updated_at
         FROM credential_subjects
         WHERE backoffice_user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute(['user_id'=>$userId]);
    $subject = $stmt->fetch();
    if ($subject || !$create) {
        return $subject ?: null;
    }

    $displayName = '';
    if (($user['role'] ?? '') === 'elite') {
        $member = mmDb()->prepare('SELECT display_name FROM elite_members WHERE user_id = :user_id LIMIT 1');
        $member->execute(['user_id'=>$userId]);
        $displayName = trim((string)$member->fetchColumn());
    }

    if ($displayName === '') {
        $email = (string)($user['email'] ?? '');
        $displayName = trim((string)strtok($email, '@'));
    }
    if ($displayName === '') {
        $displayName = 'Credential Holder';
    }

    $insert = mmDb()->prepare(
        "INSERT INTO credential_subjects (backoffice_user_id, display_name, subject_status, created_at, updated_at)
         VALUES (:user_id, :display_name, 'active', NOW(), NOW())"
    );
    $insert->execute(['user_id'=>$userId,'display_name'=>$displayName]);

    mmBackofficeAudit($userId, 'credential_subject.created', 'credential_subject', (int)mmDb()->lastInsertId());

    $stmt->execute(['user_id'=>$userId]);
    return $stmt->fetch() ?: null;
}

function mmCredentialCode(): string
{
    return 'MM-EL-' . strtoupper(bin2hex(random_bytes(4)));
}

function mmCredentialOrderLabel(string $channel): string
{
    return match ($channel) {
        'apple_wallet' => 'Zu Apple Wallet hinzufügen',
        'physical_card' => 'Physische Karte',
        'transparent_holder' => 'Transparenter Ausweishalter',
        'mysterymarket_lanyard' => 'MysteryMarket Lanyard',
        'elite_shopper_lanyard' => 'Elite Shopper Lanyard',
        'full_set' => 'Komplettset',
        'replacement_card' => 'Ersatzkarte',
        default => $channel,
    };
}

function mmCredentialTypeLabel(string $type): string
{
    return match ($type) {
        'elite_shopper' => 'Elite Shopper',
        'auditor' => 'Auditor',
        'field_credential' => 'Field Credential',
        default => $type,
    };
}
