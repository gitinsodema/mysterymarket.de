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


function mmCredentialIntegrityErrors(array $row): array
{
    if ((int)($row['is_personal_verification'] ?? 0) !== 1) {
        return ['Kein persönlicher Verify-Ausweis.'];
    }

    $errors = [];
    $required = [
        'person_name'=>'Person fehlt',
        'role_label'=>'Rolle fehlt',
        'agency_name'=>'Agentur fehlt',
        'project_name'=>'Projekt fehlt',
        'brand_name'=>'Marke / Kunde fehlt',
        'photo_asset'=>'Foto fehlt',
        'brand_logo_asset'=>'Markenlogo fehlt',
        'agency_logo_asset'=>'Agenturlogo fehlt',
        'scope_key'=>'Scope fehlt',
        'document_asset'=>'Dokument fehlt',
        'document_label'=>'Dokumentbezeichnung fehlt',
    ];

    foreach ($required as $column=>$message) {
        if (trim((string)($row[$column] ?? '')) === '') {
            $errors[] = $message;
        }
    }

    if (empty($row['valid_from'])) {
        $errors[] = 'Gültigkeitsbeginn fehlt';
    }
    if (empty($row['valid_until'])) {
        $errors[] = 'Gültigkeitsende fehlt';
    }
    if (!empty($row['valid_from']) && !empty($row['valid_until'])
        && (string)$row['valid_until'] < (string)$row['valid_from']) {
        $errors[] = 'Gültigkeitszeitraum ist ungültig';
    }
    if ((int)($row['document_enabled'] ?? 0) !== 1) {
        $errors[] = 'Dokument ist nicht aktiviert';
    }
    if ((int)($row['print_card_enabled'] ?? 0) !== 1) {
        $errors[] = 'Druckkarte ist nicht aktiviert';
    }

    $allowedScopes = ['vodafone_skopos_2026','hp_bare_retail_2025_2026'];
    $scope = trim((string)($row['scope_key'] ?? ''));
    if ($scope !== '' && !in_array($scope, $allowedScopes, true)) {
        $errors[] = 'Scope ist unbekannt';
    }

    $assetDir = rtrim((string)(mmConfig()['security']['verify_asset_dir'] ?? ''), '/');
    $base = $assetDir !== '' ? realpath($assetDir) : false;
    if ($base === false || !is_dir($base) || !is_readable($base)) {
        $errors[] = 'Privater Verify-Asset-Speicher ist nicht verfügbar';
        return array_values(array_unique($errors));
    }

    $rules = [
        'photo_asset'=>['image/png','image/jpeg','image/webp'],
        'brand_logo_asset'=>['image/png','image/jpeg','image/webp'],
        'agency_logo_asset'=>['image/png','image/jpeg','image/webp'],
        'document_asset'=>['application/pdf'],
    ];

    foreach ($rules as $column=>$allowedMime) {
        $filename = trim((string)($row[$column] ?? ''));
        if ($filename === '') {
            continue;
        }
        if (basename($filename) !== $filename) {
            $errors[] = $column . ': ungültiger Dateiname';
            continue;
        }

        $file = realpath($base . DIRECTORY_SEPARATOR . $filename);
        if ($file === false || !is_file($file) || !is_readable($file)
            || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
            $errors[] = $column . ': Datei nicht verfügbar';
            continue;
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: '';
        if (!in_array($mime, $allowedMime, true)) {
            $errors[] = $column . ': ungültiger Dateityp';
        }
    }

    return array_values(array_unique($errors));
}


function mmAppleWalletReadiness(): array
{
    $wallet = mmConfig()['apple_wallet'] ?? [];
    $issues = [];

    if (empty($wallet['enabled'])) {
        $issues[] = 'Apple Wallet ist noch nicht aktiviert.';
    }
    if (trim((string)($wallet['pass_type_identifier'] ?? '')) === '') {
        $issues[] = 'Pass Type Identifier fehlt.';
    }
    if (trim((string)($wallet['team_identifier'] ?? '')) === '') {
        $issues[] = 'Apple Team Identifier fehlt.';
    }

    $certificatePath = trim((string)($wallet['certificate_path'] ?? ''));
    if ($certificatePath === '' || !is_file($certificatePath) || !is_readable($certificatePath)) {
        $issues[] = 'Pass-Signaturzertifikat fehlt oder ist nicht lesbar.';
    }

    $wwdrPath = trim((string)($wallet['wwdr_certificate_path'] ?? ''));
    if ($wwdrPath === '' || !is_file($wwdrPath) || !is_readable($wwdrPath)) {
        $issues[] = 'Apple WWDR-Zertifikat fehlt oder ist nicht lesbar.';
    }

    return [
        'ready'=>$issues === [],
        'issues'=>$issues,
    ];
}

function mmCredentialOutputIsPhysical(string $type): bool
{
    return in_array($type, [
        'physical_card',
        'transparent_holder',
        'mysterymarket_lanyard',
        'elite_shopper_lanyard',
        'full_set',
        'replacement_card',
    ], true);
}
