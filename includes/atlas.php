<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function mmAtlasConfig(): array
{
    $config = mmConfig()['atlas'] ?? [];
    return [
        'base_url' => rtrim((string)($config['base_url'] ?? 'https://atlas.insodema.com/api/v1'), '/'),
        'token' => trim((string)($config['token'] ?? '')),
        'product' => trim((string)($config['product'] ?? 'MYSTERYMARKET')),
        'timeout_seconds' => max(2, min(20, (int)($config['timeout_seconds'] ?? 8))),
    ];
}

function mmAtlasIsConfigured(): bool
{
    $config = mmAtlasConfig();
    return $config['token'] !== ''
        && $config['token'] !== 'REPLACE_WITH_MYSTERYMARKET_ATLAS_TOKEN'
        && $config['product'] === 'MYSTERYMARKET'
        && str_starts_with($config['base_url'], 'https://');
}

function mmAtlasRequestId(): string
{
    return 'mm-' . date('YmdHis') . '-' . bin2hex(random_bytes(8));
}

function mmAtlasRequest(string $method, string $path, array $query = [], ?array $jsonBody = null): array
{
    if (!mmAtlasIsConfigured()) {
        throw new RuntimeException('ATLAS is not configured.');
    }

    $method = strtoupper(trim($method));
    if (!in_array($method, ['GET', 'POST'], true)) {
        throw new InvalidArgumentException('Unsupported ATLAS method.');
    }

    if ($path === '' || $path[0] !== '/' || str_contains($path, '..')) {
        throw new InvalidArgumentException('Invalid ATLAS path.');
    }

    $config = mmAtlasConfig();
    $url = $config['base_url'] . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $requestId = mmAtlasRequestId();
    $headers = [
        'Authorization: Bearer ' . $config['token'],
        'X-INSODEMA-Product: MYSTERYMARKET',
        'X-Request-ID: ' . $requestId,
        'Accept: application/json',
    ];

    $ch = curl_init($url);
    if ($ch === false) {
        throw new RuntimeException('Could not initialize ATLAS request.');
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => min(5, $config['timeout_seconds']),
        CURLOPT_TIMEOUT => $config['timeout_seconds'],
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    if ($method === 'POST') {
        $payload = json_encode($jsonBody ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            curl_close($ch);
            throw new RuntimeException('Could not encode ATLAS request body.');
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!is_string($raw)) {
        throw new RuntimeException('ATLAS request failed: ' . ($curlError !== '' ? $curlError : 'network error'));
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('ATLAS returned invalid JSON.');
    }

    $meta = is_array($decoded['meta'] ?? null) ? $decoded['meta'] : [];
    $remoteRequestId = trim((string)($meta['request_id'] ?? ''));
    $correlationId = trim((string)($meta['correlation_id'] ?? ''));

    if ($remoteRequestId !== '' || $correlationId !== '') {
        error_log(sprintf(
            'MysteryMarket ATLAS request local=%s remote=%s correlation=%s status=%d',
            $requestId,
            $remoteRequestId !== '' ? $remoteRequestId : '-',
            $correlationId !== '' ? $correlationId : '-',
            $status
        ));
    }

    if ($status < 200 || $status >= 300 || ($decoded['success'] ?? false) !== true) {
        $code = (string)($decoded['error']['code'] ?? 'atlas_error');
        if ($status === 401) {
            throw new RuntimeException('ATLAS authentication failed.');
        }
        if ($status === 403) {
            throw new RuntimeException('ATLAS geography scope is insufficient.');
        }
        throw new RuntimeException('ATLAS request failed: ' . $code);
    }

    return $decoded;
}

function mmAtlasCountries(): array
{
    return mmAtlasRequest('GET', '/countries');
}

function mmAtlasSubdivisions(string $countryCode): array
{
    $countryCode = strtoupper(trim($countryCode));
    if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
        throw new InvalidArgumentException('Invalid country code.');
    }
    return mmAtlasRequest('GET', '/countries/' . rawurlencode($countryCode) . '/subdivisions');
}

function mmAtlasPostalAreas(string $subdivisionAtlasId): array
{
    $id = trim($subdivisionAtlasId);
    if ($id === '') {
        throw new InvalidArgumentException('Subdivision ATLAS ID required.');
    }
    return mmAtlasRequest('GET', '/subdivisions/' . rawurlencode($id) . '/postal-areas');
}

function mmAtlasPostalArea(string $countryCode, string $postalCode): array
{
    $countryCode = strtoupper(trim($countryCode));
    $postalCode = trim($postalCode);
    if (!preg_match('/^[A-Z]{2}$/', $countryCode) || $postalCode === '' || strlen($postalCode) > 24) {
        throw new InvalidArgumentException('Invalid postal lookup.');
    }
    return mmAtlasRequest('GET', '/postal-areas/' . rawurlencode($countryCode) . '/' . rawurlencode($postalCode));
}

function mmAtlasLocalities(string $countryCode, string $postalCode = '', string $query = '', int $limit = 20): array
{
    $countryCode = strtoupper(trim($countryCode));
    if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
        throw new InvalidArgumentException('Invalid country code.');
    }

    $params = ['country_code' => $countryCode, 'limit' => max(1, min(50, $limit))];
    if ($postalCode !== '') {
        $params['postal_code'] = substr(trim($postalCode), 0, 24);
    }
    if ($query !== '') {
        $params['q'] = mb_substr(trim($query), 0, 120);
    }

    return mmAtlasRequest('GET', '/localities', $params);
}

function mmAtlasStreets(string $countryCode, string $postalCode, string $localityId, string $query, int $limit = 20): array
{
    $countryCode = strtoupper(trim($countryCode));
    $postalCode = trim($postalCode);
    $localityId = trim($localityId);
    $query = trim($query);

    if (!preg_match('/^[A-Z]{2}$/', $countryCode) || $postalCode === '' || $localityId === '' || $query === '') {
        throw new InvalidArgumentException('Incomplete street lookup.');
    }

    return mmAtlasRequest('GET', '/streets', [
        'country_code' => $countryCode,
        'postal_code' => substr($postalCode, 0, 24),
        'locality_id' => $localityId,
        'q' => mb_substr($query, 0, 120),
        'limit' => max(1, min(50, $limit)),
    ]);
}
