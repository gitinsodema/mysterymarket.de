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
    'turnstile' => [
        'site_key' => '0x4AAAAAAEkyOLYcyYVdVC_p',
        'secret_key' => 'REPLACE_WITH_CLOUDFLARE_TURNSTILE_SECRET',
        'hostname' => 'mysterymarket.de',
    ],
    'atlas' => [
        'base_url' => 'https://atlas.insodema.com/api/v1',
        'token' => 'REPLACE_WITH_MYSTERYMARKET_ATLAS_TOKEN',
        'product' => 'MYSTERYMARKET',
        'timeout_seconds' => 8,
    ],
    // Apple Wallet signing material belongs in private server storage only.
    // Never commit real certificate passwords, private keys, P12/PFX files or
    // production artwork that is intended to remain private.
    // See docs/APPLE_WALLET_RUNBOOK.md.
    'apple_wallet' => [
        'enabled' => false,
        'pass_type_identifier' => 'pass.REPLACE_WITH_PASS_TYPE_ID',
        'team_identifier' => 'REPLACE_WITH_APPLE_TEAM_ID',
        'organization_name' => 'MysteryMarket',
        'certificate_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/pass-signing.p12',
        'certificate_password' => 'REPLACE_WITH_PRIVATE_CERTIFICATE_PASSWORD',
        'wwdr_certificate_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/AppleWWDRCA.cer',
        'icon_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/icon.png',
        'icon_2x_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/icon@2x.png',
        'logo_path' => '/var/www/vhosts/mysterymarket.de/private/wallet/logo.png',
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
