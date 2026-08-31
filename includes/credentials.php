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
        $issues[] = 'Pass-Signaturzertifikat (.p12/.pfx) fehlt oder ist nicht lesbar.';
    }

    $wwdrPath = trim((string)($wallet['wwdr_certificate_path'] ?? ''));
    if ($wwdrPath === '' || !is_file($wwdrPath) || !is_readable($wwdrPath)) {
        $issues[] = 'Apple WWDR-Zertifikat fehlt oder ist nicht lesbar.';
    }

    $iconPath = trim((string)($wallet['icon_path'] ?? ''));
    if ($iconPath === '' || !is_file($iconPath) || !is_readable($iconPath)) {
        $issues[] = 'Wallet icon.png fehlt oder ist nicht lesbar.';
    } elseif ((new finfo(FILEINFO_MIME_TYPE))->file($iconPath) !== 'image/png') {
        $issues[] = 'Wallet icon.png muss eine PNG-Datei sein.';
    }

    if (!class_exists('ZipArchive')) {
        $issues[] = 'PHP ZipArchive ist nicht verfügbar.';
    }
    if (!function_exists('openssl_pkcs12_read') || !function_exists('openssl_cms_sign')) {
        $issues[] = 'PHP OpenSSL CMS/PKCS#12-Unterstützung ist nicht verfügbar.';
    }

    return [
        'ready'=>$issues === [],
        'issues'=>$issues,
    ];
}

function mmAppleWalletPassPayload(array $credential): array
{
    $wallet = mmConfig()['apple_wallet'] ?? [];
    $reference = (string)$credential['reference_code'];
    $verifyUrl = 'https://mysterymarket.de/verify?code=' . rawurlencode($reference) . '#credential';

    $payload = [
        'formatVersion'=>1,
        'passTypeIdentifier'=>(string)$wallet['pass_type_identifier'],
        'serialNumber'=>$reference,
        'teamIdentifier'=>(string)$wallet['team_identifier'],
        'organizationName'=>(string)($wallet['organization_name'] ?? 'MysteryMarket'),
        'description'=>'Projektbezogener Audit-Ausweis',
        'logoText'=>(string)($credential['brand_name'] ?: 'MysteryMarket'),
        'foregroundColor'=>'rgb(255,255,255)',
        'backgroundColor'=>'rgb(0,25,80)',
        'labelColor'=>'rgb(220,230,240)',
        'generic'=>[
            'primaryFields'=>[
                [
                    'key'=>'project',
                    'label'=>'PROJEKT',
                    'value'=>(string)$credential['project_name'],
                ],
            ],
            'secondaryFields'=>[
                [
                    'key'=>'person',
                    'label'=>'PERSON',
                    'value'=>(string)$credential['person_name'],
                ],
                [
                    'key'=>'agency',
                    'label'=>'AGENTUR',
                    'value'=>(string)$credential['agency_name'],
                ],
            ],
            'auxiliaryFields'=>[
                [
                    'key'=>'validUntil',
                    'label'=>'GÜLTIG BIS',
                    'value'=>(string)$credential['valid_until'],
                ],
                [
                    'key'=>'reference',
                    'label'=>'VERIFY',
                    'value'=>$reference,
                ],
            ],
            'backFields'=>[
                ['key'=>'role','label'=>'Rolle','value'=>(string)$credential['role_label']],
                ['key'=>'brand','label'=>'Marke / Kunde','value'=>(string)$credential['brand_name']],
                ['key'=>'projectBack','label'=>'Projekt','value'=>(string)$credential['project_name']],
                ['key'=>'agencyBack','label'=>'Agentur','value'=>(string)$credential['agency_name']],
                ['key'=>'validity','label'=>'Gültigkeit','value'=>(string)$credential['valid_from'] . ' – ' . (string)$credential['valid_until']],
                ['key'=>'verifyUrl','label'=>'Verify URL','value'=>$verifyUrl],
            ],
        ],
        'barcodes'=>[
            [
                'format'=>'PKBarcodeFormatQR',
                'message'=>$verifyUrl,
                'messageEncoding'=>'iso-8859-1',
                'altText'=>$reference,
            ],
        ],
    ];

    if (!empty($credential['valid_until'])) {
        $payload['expirationDate'] = (string)$credential['valid_until'] . 'T23:59:59Z';
    }
    if (!empty($credential['valid_from'])) {
        $payload['relevantDate'] = (string)$credential['valid_from'] . 'T00:00:00Z';
    }

    return $payload;
}

function mmAppleWalletBuildPass(array $credential): string
{
    $readiness = mmAppleWalletReadiness();
    if (empty($readiness['ready'])) {
        throw new RuntimeException('Apple Wallet ist nicht bereit: ' . implode(' ', $readiness['issues']));
    }
    if ((int)($credential['is_active'] ?? 0) !== 1) {
        throw new RuntimeException('Nur aktive Verify-Ausweise können als Wallet-Pass ausgegeben werden.');
    }

    $integrityErrors = mmCredentialIntegrityErrors($credential);
    if ($integrityErrors !== []) {
        throw new RuntimeException('Wallet-Pass blockiert: ' . implode('; ', $integrityErrors));
    }

    $wallet = mmConfig()['apple_wallet'] ?? [];
    $workspace = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR . 'mm_wallet_' . bin2hex(random_bytes(10));

    if (!mkdir($workspace, 0700, true) && !is_dir($workspace)) {
        throw new RuntimeException('Temporärer Wallet-Arbeitsbereich konnte nicht angelegt werden.');
    }

    try {
        $passJson = json_encode(
            mmAppleWalletPassPayload($credential),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
        file_put_contents($workspace . '/pass.json', $passJson);

        $iconPath = (string)$wallet['icon_path'];
        if (!copy($iconPath, $workspace . '/icon.png')) {
            throw new RuntimeException('Wallet icon.png konnte nicht vorbereitet werden.');
        }

        $icon2x = trim((string)($wallet['icon_2x_path'] ?? ''));
        if ($icon2x !== '' && is_file($icon2x) && is_readable($icon2x)) {
            if ((new finfo(FILEINFO_MIME_TYPE))->file($icon2x) !== 'image/png') {
                throw new RuntimeException('Wallet icon@2x.png muss PNG sein.');
            }
            copy($icon2x, $workspace . '/icon@2x.png');
        }

        $logoPath = trim((string)($wallet['logo_path'] ?? ''));
        if ($logoPath !== '' && is_file($logoPath) && is_readable($logoPath)) {
            if ((new finfo(FILEINFO_MIME_TYPE))->file($logoPath) !== 'image/png') {
                throw new RuntimeException('Wallet logo.png muss PNG sein.');
            }
            copy($logoPath, $workspace . '/logo.png');
        }

        $manifest = [];
        foreach (['pass.json','icon.png','icon@2x.png','logo.png'] as $filename) {
            $path = $workspace . '/' . $filename;
            if (is_file($path)) {
                $manifest[$filename] = sha1_file($path);
            }
        }
        file_put_contents(
            $workspace . '/manifest.json',
            json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );

        $p12 = file_get_contents((string)$wallet['certificate_path']);
        if ($p12 === false) {
            throw new RuntimeException('Wallet-Signaturzertifikat konnte nicht gelesen werden.');
        }

        $certs = [];
        if (!openssl_pkcs12_read($p12, $certs, (string)($wallet['certificate_password'] ?? ''))) {
            throw new RuntimeException('Wallet-Signaturzertifikat konnte nicht entsperrt werden.');
        }

        $certificate = openssl_x509_read((string)($certs['cert'] ?? ''));
        $privateKey = openssl_pkey_get_private((string)($certs['pkey'] ?? ''));
        if ($certificate === false || $privateKey === false) {
            throw new RuntimeException('Wallet-Zertifikat oder privater Schlüssel ist ungültig.');
        }

        $signaturePath = $workspace . '/signature';
        $signed = openssl_cms_sign(
            $workspace . '/manifest.json',
            $signaturePath,
            $certificate,
            $privateKey,
            [],
            OPENSSL_CMS_DETACHED | OPENSSL_CMS_BINARY,
            OPENSSL_ENCODING_DER,
            (string)$wallet['wwdr_certificate_path']
        );
        if (!$signed || !is_file($signaturePath) || filesize($signaturePath) < 1) {
            throw new RuntimeException('Wallet-Pass konnte nicht signiert werden.');
        }

        $output = tempnam(sys_get_temp_dir(), 'mm_pkpass_');
        if ($output === false) {
            throw new RuntimeException('Temporäre Wallet-Ausgabedatei konnte nicht erzeugt werden.');
        }
        @unlink($output);
        $output .= '.pkpass';

        $zip = new ZipArchive();
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Wallet-Paket konnte nicht erstellt werden.');
        }

        foreach (['pass.json','manifest.json','signature','icon.png','icon@2x.png','logo.png'] as $filename) {
            $path = $workspace . '/' . $filename;
            if (is_file($path)) {
                $zip->addFile($path, $filename);
            }
        }
        $zip->close();

        return $output;
    } finally {
        foreach (glob($workspace . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($workspace);
    }
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
