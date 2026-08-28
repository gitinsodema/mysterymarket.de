<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function mmDb(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = mmConfig()['db'] ?? [];
    $dsn = trim((string)($db['dsn'] ?? ''));
    $user = (string)($db['user'] ?? '');
    $password = (string)($db['password'] ?? '');

    if ($dsn === '') {
        throw new RuntimeException('Database is not configured.');
    }

    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
