<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$path = trim((string)($_SERVER['argv'][1] ?? ''));
if ($path === '' || !is_file($path) || !is_readable($path)) {
    fwrite(STDERR, "Usage: php scripts/wallet-package-review.php /path/to/pass.pkpass\n");
    exit(2);
}

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "[FAIL] ZipArchive unavailable.\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    fwrite(STDERR, "[FAIL] File is not a readable ZIP/pkpass package.\n");
    exit(1);
}

$required = ['pass.json','manifest.json','signature','icon.png'];
$failures = 0;

foreach ($required as $name) {
    if ($zip->locateName($name) === false) {
        fwrite(STDERR, "[FAIL] Missing package member: {$name}\n");
        $failures++;
    } else {
        echo "[PASS] {$name} present\n";
    }
}

$passRaw = $zip->getFromName('pass.json');
$manifestRaw = $zip->getFromName('manifest.json');
$signature = $zip->getFromName('signature');

$pass = is_string($passRaw) ? json_decode($passRaw, true) : null;
$manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;

if (!is_array($pass)) {
    fwrite(STDERR, "[FAIL] pass.json is missing or invalid JSON.\n");
    $failures++;
} else {
    foreach (['formatVersion','passTypeIdentifier','serialNumber','teamIdentifier','organizationName','description','generic','barcodes'] as $key) {
        if (!array_key_exists($key, $pass) || $pass[$key] === '' || $pass[$key] === []) {
            fwrite(STDERR, "[FAIL] pass.json missing/empty key: {$key}\n");
            $failures++;
        }
    }
    if ($failures === 0) {
        echo "[PASS] pass.json core contract complete\n";
    }

    echo "[INFO] serialNumber=" . (string)($pass['serialNumber'] ?? '—') . "\n";
    echo "[INFO] passTypeIdentifier=" . (string)($pass['passTypeIdentifier'] ?? '—') . "\n";
    echo "[INFO] expirationDate=" . (string)($pass['expirationDate'] ?? '—') . "\n";
}

if (!is_array($manifest)) {
    fwrite(STDERR, "[FAIL] manifest.json is missing or invalid JSON.\n");
    $failures++;
} else {
    foreach ($manifest as $name=>$expectedSha1) {
        $data = $zip->getFromName((string)$name);
        if (!is_string($data)) {
            fwrite(STDERR, "[FAIL] Manifest references missing file: {$name}\n");
            $failures++;
            continue;
        }
        $actual = sha1($data);
        if (!hash_equals((string)$expectedSha1, $actual)) {
            fwrite(STDERR, "[FAIL] Manifest digest mismatch: {$name}\n");
            $failures++;
        }
    }
    if ($failures === 0) {
        echo "[PASS] manifest digests match package members\n";
    }
}

if (!is_string($signature) || strlen($signature) < 64) {
    fwrite(STDERR, "[FAIL] signature is missing or implausibly small.\n");
    $failures++;
} else {
    echo "[PASS] detached signature payload present\n";
}

$zip->close();

if ($failures === 0) {
    echo "MYSTERYMARKET_APPLE_WALLET_PACKAGE_OK\n";
    exit(0);
}

fwrite(STDERR, "MYSTERYMARKET_APPLE_WALLET_PACKAGE_FAILED failures={$failures}\n");
exit(1);
