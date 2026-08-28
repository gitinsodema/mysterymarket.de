<?php
declare(strict_types=1);

function mmConfig(): array
{
    static $config;
    if (is_array($config)) {
        return $config;
    }

    $path = dirname(__DIR__) . '/config/local.php';
    if (!is_file($path)) {
        $config = [
            'db' => [],
            'mail' => [
                'notification_email' => '',
                'from_email' => 'hello@mysterymarket.de',
            ],
            'legal' => [
                'brand' => 'MysteryMarket',
                'legal_form' => 'Einzelunternehmen',
                'owner_name' => '',
                'street' => '',
                'postal_code' => '',
                'city' => 'Düsseldorf',
                'country' => 'Deutschland',
                'email' => 'hello@mysterymarket.de',
                'phone' => '',
                'vat_id' => '',
                'small_business_regulation' => false,
                'hosting_provider_name' => '',
                'hosting_provider_address' => '',
                'server_location' => 'Deutschland',
                'privacy_supervisory_authority' => '',
            ],
        ];
        return $config;
    }

    $loaded = require $path;
    if (!is_array($loaded)) {
        throw new RuntimeException('config/local.php must return an array.');
    }

    $config = $loaded;
    return $config;
}

function mmLegal(): array
{
    return mmConfig()['legal'] ?? [];
}

function mmEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
