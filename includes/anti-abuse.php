<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function mmTurnstileSiteKey(): string
{
    return trim((string)(mmConfig()['turnstile']['site_key'] ?? ''));
}

function mmTurnstileVerify(string $token, string $expectedAction): array
{
    $cfg = mmConfig()['turnstile'] ?? [];
    $secret = trim((string)($cfg['secret_key'] ?? ''));
    $expectedHostname = strtolower(trim((string)($cfg['hostname'] ?? 'mysterymarket.de')));

    if ($secret === '' || $token === '' || $expectedAction === '') {
        return ['ok' => false, 'reason' => 'not_configured_or_missing_token'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'reason' => 'curl_unavailable'];
    }

    $payload = [
        'secret' => $secret,
        'response' => $token,
    ];

    $remoteIp = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remoteIp !== '') {
        $payload['remoteip'] = $remoteIp;
    }

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    if ($ch === false) {
        return ['ok' => false, 'reason' => 'curl_init_failed'];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!is_string($raw) || $raw === '' || $httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'reason' => $curlError !== '' ? 'request_failed' : 'invalid_http_response'];
    }

    $response = json_decode($raw, true);
    if (!is_array($response) || empty($response['success'])) {
        return ['ok' => false, 'reason' => 'challenge_failed'];
    }

    $hostname = strtolower(trim((string)($response['hostname'] ?? '')));
    if ($expectedHostname !== '' && $hostname !== $expectedHostname) {
        return ['ok' => false, 'reason' => 'hostname_mismatch'];
    }

    $action = trim((string)($response['action'] ?? ''));
    if (!hash_equals($expectedAction, $action)) {
        return ['ok' => false, 'reason' => 'action_mismatch'];
    }

    return [
        'ok' => true,
        'reason' => 'verified',
        'hostname' => $hostname,
        'action' => $action,
    ];
}
