<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/backoffice-auth.php';

header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, noarchive');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit;
}

if (!mmBackofficeVerifyCsrf((string)($_POST['csrf'] ?? ''))) {
    http_response_code(400);
    exit('Invalid request');
}

mmBackofficeLogout();
header('Location: /backoffice/login.php', true, 303);
exit;
