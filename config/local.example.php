<?php
declare(strict_types=1);

return [
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=mysterymarket;charset=utf8mb4',
        'user' => 'mysterymarket',
        'password' => '',
    ],
    'mail' => [
        'notification_email' => 'REPLACE_WITH_INTERNAL_NOTIFICATION_EMAIL',
        'from_email' => 'hello@mysterymarket.de',
    ],
    'security' => [
        'rate_limit_salt' => 'REPLACE_WITH_A_LONG_RANDOM_SECRET',
        'verify_asset_dir' => '/var/www/vhosts/mysterymarket.de/private/verify-assets',
    ],
    'atlas' => [
        'base_url' => 'https://atlas.insodema.com/api/v1',
        'token' => 'REPLACE_WITH_MYSTERYMARKET_ATLAS_TOKEN',
        'product' => 'MYSTERYMARKET',
        'timeout_seconds' => 8,
    ],
    'legal' => [
        'brand' => 'MysteryMarket',
        'legal_form' => 'Einzelunternehmen',
        'owner_name' => 'REPLACE_WITH_VERIFIED_OWNER_NAME',
        'street' => 'REPLACE_WITH_VERIFIED_STREET',
        'postal_code' => 'REPLACE_WITH_VERIFIED_POSTAL_CODE',
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
