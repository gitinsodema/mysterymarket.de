<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$root = dirname(__DIR__);
$php = PHP_BINARY;

$checks = [
    ['label'=>'preflight', 'cmd'=>[$php, $root . '/scripts/preflight.php']],
    ['label'=>'backoffice integrity', 'cmd'=>[$php, $root . '/scripts/backoffice-review.php']],
    ['label'=>'credential service integrity', 'cmd'=>[$php, $root . '/scripts/credentials-review.php']],
    ['label'=>'ATLAS smoke', 'cmd'=>[$php, $root . '/scripts/atlas-smoke.php']],
    ['label'=>'Apple Wallet readiness report', 'cmd'=>[$php, $root . '/scripts/wallet-readiness.php']],
];

$failures = 0;

foreach ($checks as $check) {
    echo "\n== {$check['label']} ==\n";
    $command = implode(' ', array_map('escapeshellarg', $check['cmd']));
    passthru($command, $code);

    if ($code === 0) {
        echo "[PASS] {$check['label']}\n";
    } else {
        fwrite(STDERR, "[FAIL] {$check['label']}\n");
        $failures++;
    }
}

echo "\n== R1.2 external prerequisites ==\n";
echo "[INFO] Apple Wallet signing material is allowed to remain NOT_READY before provisioning.\n";
echo "[INFO] Printer-specific integration is explicitly deferred and is not a release blocker.\n";

if ($failures === 0) {
    echo "MYSTERYMARKET_R1_2_TECHNICAL_GATE_OK\n";
    exit(0);
}

fwrite(STDERR, "MYSTERYMARKET_R1_2_TECHNICAL_GATE_FAILED failures={$failures}\n");
exit(1);
