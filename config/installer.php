<?php

// config for SoftCortex/Installer
return [
    // Product information
    'product' => [
        'name' => env('APP_NAME', 'Laravel Application'),
        'version' => '1.0.0',
        'description' => 'Professional Laravel application with installer wizard',
    ],

    // Server requirements
    'requirements' => [
        'php' => '8.2',
        'extensions' => [
            'pdo',
            'openssl',
            'mbstring',
            'tokenizer',
            'json',
            'curl',
            'xml',
            'ctype',
            'fileinfo',
        ],
        'directories' => [
            'storage',
            'storage/app',
            'storage/framework',
            'storage/logs',
            'bootstrap/cache',
        ],
    ],

    // License verification
    'license' => [
        'enabled' => env('LICENSE_ENABLED', true),
        // Envato Personal Token - Get from: https://build.envato.com/create-token/
        // Required scopes: View and search Envato sites, View the user's account username
        'envato_personal_token' => env('ENVATO_PERSONAL_TOKEN', ''),
        // Optional: Your Envato item ID for additional validation
        'envato_item_id' => env('ENVATO_ITEM_ID', ''),
    ],

    // Routes
    'routes' => [
        'prefix' => 'install',
        'middleware' => 'installer',
        'redirect_after_install' => 'dashboard',
    ],

    // Admin role
    'admin' => [
        'role' => 'admin',
        'create_role_if_missing' => true,
    ],
];
