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
    ['label'=>'ATLAS smoke', 'cmd'=>[$php, $root . '/scripts/atlas-smoke.php']],
];

$failures = 0;

foreach ($checks as $check) {
    $command = implode(' ', array_map('escapeshellarg', $check['cmd']));
    passthru($command, $code);
    if ($code === 0) {
        echo "[PASS] {$check['label']}\n";
    } else {
        fwrite(STDERR, "[FAIL] {$check['label']}\n");
        $failures++;
    }
}

if ($failures === 0) {
    echo "MYSTERYMARKET_R1_1_TECHNICAL_GATE_OK\n";
    exit(0);
}

fwrite(STDERR, "MYSTERYMARKET_R1_1_TECHNICAL_GATE_FAILED failures={$failures}\n");
exit(1);
