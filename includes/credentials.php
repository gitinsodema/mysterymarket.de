<?php
declare(strict_types=1);

require_once __DIR__ . '/backoffice-auth.php';

function mmCredentialOutputLabel(string $type): string
{
    return match ($type) {
        'apple_wallet' => 'Zu Apple Wallet hinzufügen',
        'print_card' => 'Druckansicht öffnen',
        'physical_card' => 'Physische Karte',
        'transparent_holder' => 'Transparenter Ausweishalter',
        'mysterymarket_lanyard' => 'MysteryMarket Lanyard',
        'elite_shopper_lanyard' => 'Elite Shopper Lanyard',
        'full_set' => 'Komplettset',
        'replacement_card' => 'Ersatzkarte',
        default => $type,
    };
}

function mmCredentialVerifyState(array $row): string
{
    if ((int)($row['is_active'] ?? 0) !== 1) {
        return 'disabled';
    }

    $today = date('Y-m-d');
    if (!empty($row['valid_from']) && (string)$row['valid_from'] > $today) {
        return 'pending';
    }
    if (!empty($row['valid_until']) && (string)$row['valid_until'] < $today) {
        return 'expired';
    }

    return 'active';
}

function mmCredentialProjectLabel(array $row): string
{
    $parts = array_filter([
        trim((string)($row['brand_name'] ?? '')),
        trim((string)($row['agency_name'] ?? '')),
    ]);
    return $parts ? implode(' / ', $parts) : (string)($row['public_title'] ?? $row['reference_code'] ?? '');
}


function mmCredentialGenerateVerifyReference(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $reference = 'MM-';
        for ($i = 0; $i < 8; $i++) {
            $reference .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $stmt = mmDb()->prepare('SELECT 1 FROM audit_verifications WHERE reference_code = :reference LIMIT 1');
        $stmt->execute(['reference'=>$reference]);
        if (!$stmt->fetchColumn()) {
            return $reference;
        }
    }

    throw new RuntimeException('Keine eindeutige Verify-Referenz konnte erzeugt werden.');
}

function mmCredentialDateOrNull(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date
        || ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))
        || $date->format('Y-m-d') !== $value) {
        throw new InvalidArgumentException('Ungültiges Datum.');
    }

    return $value;
}


function mmCredentialStoreUploadedAsset(array $file, int $credentialId, string $type): string
{
    $allowedTypes = [
        'photo' => ['extensions'=>['png','jpg','jpeg','webp'], 'mime'=>['image/png','image/jpeg','image/webp'], 'max'=>5 * 1024 * 1024],
        'brand_logo' => ['extensions'=>['png','jpg','jpeg','webp'], 'mime'=>['image/png','image/jpeg','image/webp'], 'max'=>5 * 1024 * 1024],
        'agency_logo' => ['extensions'=>['png','jpg','jpeg','webp'], 'mime'=>['image/png','image/jpeg','image/webp'], 'max'=>5 * 1024 * 1024],
        'document' => ['extensions'=>['pdf'], 'mime'=>['application/pdf'], 'max'=>10 * 1024 * 1024],
    ];

    if (!isset($allowedTypes[$type])) {
        throw new InvalidArgumentException('Ungültiger Asset-Typ.');
    }

    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Datei-Upload fehlgeschlagen.');
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    $original = (string)($file['name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if ($tmp === '' || !is_uploaded_file($tmp) || $size < 1 || $size > $allowedTypes[$type]['max']) {
        throw new RuntimeException('Ungültige oder zu große Datei.');
    }

    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes[$type]['extensions'], true)) {
        throw new RuntimeException('Dateiformat nicht erlaubt.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
    if (!in_array($mime, $allowedTypes[$type]['mime'], true)) {
        throw new RuntimeException('Dateiinhalt entspricht keinem erlaubten MIME-Typ.');
    }

    $assetDir = rtrim((string)(mmConfig()['security']['verify_asset_dir'] ?? ''), '/');
    $base = $assetDir !== '' ? realpath($assetDir) : false;
    if ($base === false || !is_dir($base) || !is_writable($base)) {
        throw new RuntimeException('Privates Verify-Asset-Verzeichnis ist nicht beschreibbar.');
    }

    $safeExt = $extension === 'jpeg' ? 'jpg' : $extension;
    $filename = sprintf(
        'cred_%d_%s_%s.%s',
        $credentialId,
        $type,
        bin2hex(random_bytes(6)),
        $safeExt
    );

    $target = $base . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Datei konnte nicht in den privaten Verify-Speicher verschoben werden.');
    }

    @chmod($target, 0640);
    return $filename;
}
