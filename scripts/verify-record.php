<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function vrUsage(): never
{
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  php scripts/verify-record.php audit-personal\n");
    fwrite(STDERR, "  php scripts/verify-record.php list-personal\n");
    fwrite(STDERR, "  php scripts/verify-record.php status <reference>\n");
    fwrite(STDERR, "  php scripts/verify-record.php validity <reference> <YYYY-MM-DD|-> <YYYY-MM-DD|->\n");
    fwrite(STDERR, "  php scripts/verify-record.php activate <reference>\n");
    fwrite(STDERR, "  php scripts/verify-record.php deactivate <reference>\n");
    fwrite(STDERR, "  php scripts/verify-record.php scope <reference> <scope-key|->\n");
    fwrite(STDERR, "  php scripts/verify-record.php clone-personal <source-reference> <agency> <project> <brand> <YYYY-MM-DD|-> <YYYY-MM-DD|->\n");
    exit(2);
}

function vrDate(string $value): ?string
{
    $value = trim($value);
    if ($value === '-' || $value === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$dt || ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) || $dt->format('Y-m-d') !== $value) {
        fwrite(STDERR, "[FAIL] Invalid date: {$value}. Expected YYYY-MM-DD or -.\n");
        exit(1);
    }
    return $value;
}

function vrReference(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $value = 'MM-';
    for ($i = 0; $i < 8; $i++) {
        $value .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $value;
}

$args = $_SERVER['argv'] ?? [];
$action = strtolower(trim((string)($args[1] ?? '')));
$reference = strtoupper(trim((string)($args[2] ?? '')));

if (!in_array($action, ['audit-personal','list-personal','status','validity','activate','deactivate','scope','clone-personal'], true)) {
    vrUsage();
}

$pdo = mmDb();

function vrPersonalIntegrityErrors(array $row): array
{
    if ((int)($row['is_personal_verification'] ?? 0) !== 1) {
        return [];
    }

    $errors = [];
    $requiredText = [
        'person_name' => 'person_name',
        'role_label' => 'role_label',
        'agency_name' => 'agency_name',
        'project_name' => 'project_name',
        'brand_name' => 'brand_name',
        'photo_asset' => 'photo_asset',
        'brand_logo_asset' => 'brand_logo_asset',
        'agency_logo_asset' => 'agency_logo_asset',
        'scope_key' => 'scope_key',
        'document_asset' => 'document_asset',
        'document_label' => 'document_label',
    ];

    foreach ($requiredText as $column => $label) {
        if (trim((string)($row[$column] ?? '')) === '') {
            $errors[] = $label . ' missing';
        }
    }

    if (empty($row['valid_from'])) {
        $errors[] = 'valid_from missing';
    }
    if (empty($row['valid_until'])) {
        $errors[] = 'valid_until missing';
    }
    if (!empty($row['valid_from']) && !empty($row['valid_until']) && (string)$row['valid_until'] < (string)$row['valid_from']) {
        $errors[] = 'validity range invalid';
    }
    if ((int)($row['document_enabled'] ?? 0) !== 1) {
        $errors[] = 'document not enabled';
    }
    if ((int)($row['print_card_enabled'] ?? 0) !== 1) {
        $errors[] = 'print card not enabled';
    }

    return $errors;
}

function vrFetchIntegrityRow(PDO $pdo, string $reference): array
{
    $stmt = $pdo->prepare(
        'SELECT reference_code, person_name, role_label, agency_name, project_name, brand_name,
                valid_from, valid_until, photo_asset, brand_logo_asset, agency_logo_asset,
                scope_key, document_asset, document_label, document_enabled,
                print_card_enabled, is_personal_verification, is_active
         FROM audit_verifications
         WHERE reference_code = :reference
         LIMIT 1'
    );
    $stmt->execute(['reference' => $reference]);
    return $stmt->fetch() ?: [];
}

if ($action === 'audit-personal') {
    $stmt = $pdo->query(
        'SELECT reference_code, person_name, role_label, agency_name, project_name, brand_name,
                valid_from, valid_until, photo_asset, brand_logo_asset, agency_logo_asset,
                scope_key, document_asset, document_label, document_enabled,
                print_card_enabled, is_personal_verification, is_active
         FROM audit_verifications
         WHERE is_personal_verification = 1
         ORDER BY id ASC'
    );

    $failures = 0;
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $errors = vrPersonalIntegrityErrors($row);
        $referenceCode = (string)$row['reference_code'];

        if (!$errors) {
            echo "[PASS] {$referenceCode} credential integrity complete"
                . ((int)$row['is_active'] === 1 ? ' · active' : ' · inactive') . PHP_EOL;
            continue;
        }

        $state = (int)$row['is_active'] === 1 ? '[FAIL]' : '[WARN]';
        echo $state . " {$referenceCode}: " . implode('; ', $errors) . PHP_EOL;
        if ((int)$row['is_active'] === 1) {
            $failures++;
        }
    }

    exit($failures === 0 ? 0 : 1);
}

if ($action === 'list-personal') {
    $stmt = $pdo->query(
        'SELECT reference_code, person_name, agency_name, project_name, brand_name,
                valid_from, valid_until, scope_key, document_enabled, is_active
         FROM audit_verifications
         WHERE is_personal_verification = 1
         ORDER BY id DESC'
    );
    foreach ($stmt->fetchAll() ?: [] as $row) {
        echo implode(' | ', [
            (string)$row['reference_code'],
            (string)($row['person_name'] ?: '—'),
            (string)($row['agency_name'] ?: '—'),
            (string)($row['brand_name'] ?: '—'),
            (string)($row['valid_from'] ?: 'open'),
            (string)($row['valid_until'] ?: 'open'),
            (string)($row['scope_key'] ?: '—'),
            'document=' . ((int)$row['document_enabled'] === 1 ? 'yes' : 'no'),
            'active=' . ((int)$row['is_active'] === 1 ? 'yes' : 'no'),
        ]) . PHP_EOL;
    }
    exit(0);
}

if (!preg_match('/^[A-Z0-9-]{4,64}$/', $reference)) {
    vrUsage();
}

if ($action === 'clone-personal') {
    $agency = trim((string)($args[3] ?? ''));
    $project = trim((string)($args[4] ?? ''));
    $brand = trim((string)($args[5] ?? ''));
    $validFrom = vrDate((string)($args[6] ?? '-'));
    $validUntil = vrDate((string)($args[7] ?? '-'));

    if ($agency === '' || $project === '' || $brand === ''
        || mb_strlen($agency) > 200 || mb_strlen($project) > 200 || mb_strlen($brand) > 200) {
        fwrite(STDERR, "[FAIL] Agency, project and brand are required and must be <= 200 characters.\n");
        exit(1);
    }

    $src = $pdo->prepare(
        'SELECT person_name, role_label, photo_asset
         FROM audit_verifications
         WHERE reference_code = :reference
           AND is_personal_verification = 1
         LIMIT 1'
    );
    $src->execute(['reference' => $reference]);
    $source = $src->fetch();

    if (!$source) {
        fwrite(STDERR, "[FAIL] Personal source record not found: {$reference}\n");
        exit(1);
    }

    for ($attempt = 0; $attempt < 20; $attempt++) {
        $newReference = vrReference();
        $check = $pdo->prepare('SELECT 1 FROM audit_verifications WHERE reference_code = :reference');
        $check->execute(['reference' => $newReference]);
        if (!$check->fetchColumn()) {
            break;
        }
        $newReference = '';
    }

    if ($newReference === '') {
        fwrite(STDERR, "[FAIL] Could not generate a unique Verify reference.\n");
        exit(1);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO audit_verifications
        (reference_code, public_title, public_partner, public_client, valid_from, valid_until,
         confidentiality_mode, public_note, person_name, role_label, agency_name, project_name,
         brand_name, photo_asset, brand_logo_asset, agency_logo_asset, scope_key,
         document_asset, document_label, document_enabled, print_card_enabled,
         is_personal_verification, is_active)
        VALUES
        (:reference_code, :public_title, :public_partner, :public_client, :valid_from, :valid_until,
         :confidentiality_mode, :public_note, :person_name, :role_label, :agency_name, :project_name,
         :brand_name, :photo_asset, NULL, NULL, NULL,
         NULL, NULL, 0, 1, 1, 0)'
    );

    $stmt->execute([
        'reference_code' => $newReference,
        'public_title' => $project,
        'public_partner' => $agency,
        'public_client' => $brand,
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
        'confidentiality_mode' => 'public',
        'public_note' => 'Persönliche Audit-Legitimation.',
        'person_name' => $source['person_name'],
        'role_label' => $source['role_label'],
        'agency_name' => $agency,
        'project_name' => $project,
        'brand_name' => $brand,
        'photo_asset' => $source['photo_asset'],
    ]);

    echo "[PASS] Personal Verify record cloned inactive without logos, document or project scope.\n";
    echo "Reference: {$newReference}\n";
    echo "URL: https://mysterymarket.de/verify.php?code=" . rawurlencode($newReference) . "#credential\n";
    exit(0);
}

$exists = $pdo->prepare('SELECT 1 FROM audit_verifications WHERE reference_code = :reference LIMIT 1');
$exists->execute(['reference' => $reference]);
if (!$exists->fetchColumn()) {
    fwrite(STDERR, "[FAIL] Unknown Verify reference: {$reference}\n");
    exit(1);
}

if ($action === 'status') {
    $stmt = $pdo->prepare(
        'SELECT reference_code, person_name, agency_name, project_name, brand_name,
                valid_from, valid_until, scope_key, document_asset, document_enabled,
                print_card_enabled, is_personal_verification, is_active
         FROM audit_verifications
         WHERE reference_code = :reference'
    );
    $stmt->execute(['reference' => $reference]);
    foreach ($stmt->fetch() ?: [] as $key => $value) {
        echo $key . ': ' . ($value === null || $value === '' ? '—' : (string)$value) . PHP_EOL;
    }
    exit(0);
}

if ($action === 'scope') {
    $scopeKey = trim((string)($args[3] ?? ''));
    $allowedScopes = ['vodafone_skopos_2026','hp_bare_retail_2025_2026'];

    if ($scopeKey === '-') {
        $scopeKey = null;
    } elseif (!in_array($scopeKey, $allowedScopes, true)) {
        fwrite(STDERR, "[FAIL] Unknown scope key. Allowed: " . implode(', ', $allowedScopes) . " or -\n");
        exit(1);
    }

    $stmt = $pdo->prepare(
        'UPDATE audit_verifications SET scope_key = :scope_key WHERE reference_code = :reference'
    );
    $stmt->execute(['scope_key' => $scopeKey, 'reference' => $reference]);

    echo "[PASS] Scope updated for {$reference}: " . ($scopeKey ?? 'none') . PHP_EOL;
    exit(0);
}

if ($action === 'validity') {
    $validFrom = vrDate((string)($args[3] ?? ''));
    $validUntil = vrDate((string)($args[4] ?? ''));

    if ($validFrom !== null && $validUntil !== null && $validUntil < $validFrom) {
        fwrite(STDERR, "[FAIL] valid_until must not be before valid_from.\n");
        exit(1);
    }

    $stmt = $pdo->prepare(
        'UPDATE audit_verifications
         SET valid_from = :valid_from, valid_until = :valid_until
         WHERE reference_code = :reference'
    );
    $stmt->execute([
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
        'reference' => $reference,
    ]);

    echo "[PASS] Validity updated for {$reference}: "
        . ($validFrom ?? 'open') . " -> " . ($validUntil ?? 'open') . PHP_EOL;
    exit(0);
}

$isActive = $action === 'activate' ? 1 : 0;

if ($isActive === 1) {
    $integrityRow = vrFetchIntegrityRow($pdo, $reference);
    $integrityErrors = vrPersonalIntegrityErrors($integrityRow);

    if ($integrityErrors) {
        fwrite(STDERR, "[FAIL] {$reference} cannot be activated: "
            . implode('; ', $integrityErrors) . PHP_EOL);
        exit(1);
    }
}

$stmt = $pdo->prepare('UPDATE audit_verifications SET is_active = :is_active WHERE reference_code = :reference');
$stmt->execute(['is_active' => $isActive, 'reference' => $reference]);

echo "[PASS] {$reference} " . ($isActive ? 'activated after integrity validation' : 'deactivated') . PHP_EOL;
