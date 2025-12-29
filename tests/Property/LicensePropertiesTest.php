<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use SoftCortex\Installer\Services\InstallerService;
use SoftCortex\Installer\Services\LicenseService;

beforeEach(function () {
    // Clean up any existing installer files
    $installedFile = storage_path('app/.installed');
    $settingsFile = storage_path('app/installer-settings.json');

    if (File::exists($installedFile)) {
        File::delete($installedFile);
    }

    if (File::exists($settingsFile)) {
        File::delete($settingsFile);
    }

    // Set test configuration for Envato API
    config(['installer.license.envato_personal_token' => 'test-token-'.uniqid()]);
    config(['installer.license.envato_item_id' => '12345678']);
});

afterEach(function () {
    // Clean up installer files
    $installedFile = storage_path('app/.installed');
    $settingsFile = storage_path('app/installer-settings.json');

    if (File::exists($installedFile)) {
        File::delete($installedFile);
    }

    if (File::exists($settingsFile)) {
        File::delete($settingsFile);
    }
});

// Feature: envato-installer-wizard, Property 10: License Verification API Communication
test('license verification sends purchase code to Envato API with Bearer token', function () {
    // Valid Envato purchase code format (UUID)
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    Http::fake([
        'api.envato.com/*' => Http::response([
            'item' => [
                'id' => '12345678',
                'name' => 'Test Product',
            ],
            'buyer' => 'Test Buyer',
            'sold_at' => '2024-01-01T00:00:00Z',
            'supported_until' => '2025-01-01T00:00:00Z',
            'license' => 'regular',
        ], 200),
    ]);

    $licenseService = app(LicenseService::class);

    $result = $licenseService->verify($purchaseCode);

    // Verify Envato API was called with correct parameters
    Http::assertSent(function ($request) use ($purchaseCode) {
        return str_contains($request->url(), 'api.envato.com') &&
               str_contains($request->url(), 'code='.$purchaseCode) &&
               $request->hasHeader('Authorization') &&
               str_contains($request->header('Authorization')[0], 'Bearer');
    });

    expect($result->isValid())->toBeTrue();
})->repeat(100);

// Feature: envato-installer-wizard, Property 11: License Storage After Verification
test('successful license verification stores hashed license reference', function () {
    // Valid Envato purchase code format (UUID)
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    Http::fake([
        'api.envato.com/*' => Http::response([
            'item' => [
                'id' => '12345678',
                'name' => 'Test Product',
            ],
            'buyer' => 'Test Buyer',
            'sold_at' => '2024-01-01T00:00:00Z',
            'supported_until' => '2025-01-01T00:00:00Z',
            'license' => 'regular',
        ], 200),
    ]);

    $licenseService = app(LicenseService::class);
    $installer = app(InstallerService::class);

    $result = $licenseService->verify($purchaseCode);

    expect($result->isValid())->toBeTrue();

    // Verify hashed license is stored
    $storedHash = $installer->getSetting('license_hash');
    expect($storedHash)->not->toBeNull();
    expect($storedHash)->toBe(hash('sha256', $purchaseCode));

    // Verify license data is stored
    $licenseData = $installer->getSetting('license_data');
    expect($licenseData)->not->toBeNull();

    $data = json_decode($licenseData, true);
    expect($data)->toHaveKey('item_name');
    expect($data)->toHaveKey('buyer');
    expect($data)->toHaveKey('verified_at');
    expect($data)->toHaveKey('license_type');
})->repeat(100);

// Feature: envato-installer-wizard, Property 12: No API Token Storage
test('license verification never stores Envato API token in application', function () {
    // Valid Envato purchase code format (UUID)
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    Http::fake([
        'api.envato.com/*' => Http::response([
            'item' => ['id' => '12345678', 'name' => 'Test Product'],
            'buyer' => 'Test Buyer',
            'license' => 'regular',
        ], 200),
    ]);

    $licenseService = app(LicenseService::class);

    $licenseService->verify($purchaseCode);

    // Check settings file - should not contain API token patterns
    $settingsFile = storage_path('app/installer-settings.json');
    expect(File::exists($settingsFile))->toBeTrue();

    $settingsContent = File::get($settingsFile);
    $settings = json_decode($settingsContent, true);

    // Check for common token patterns in all settings
    foreach ($settings as $key => $value) {
        $valueStr = is_string($value) ? $value : json_encode($value);

        // Check for common token patterns
        expect($valueStr)->not->toContain('Bearer');
        expect($valueStr)->not->toContain('test-token');
        expect($valueStr)->not->toContain('api_key');
        expect($valueStr)->not->toContain('envato_token');
        expect($valueStr)->not->toContain('personal_token');

        // Verify it's not the purchase code (only hash should be stored)
        if ($key === 'license_hash') {
            expect($value)->not->toBe($purchaseCode);
            expect(strlen($value))->toBe(64); // SHA-256 hash length
        }
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 23: HTTPS for License API
test('license verification uses HTTPS protocol for Envato API requests', function () {
    // Valid Envato purchase code format (UUID)
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    Http::fake([
        'api.envato.com/*' => Http::response([
            'item' => ['id' => '12345678', 'name' => 'Test'],
            'buyer' => 'Test',
            'license' => 'regular',
        ], 200),
    ]);

    $licenseService = app(LicenseService::class);
    $licenseService->verify($purchaseCode);

    // Verify HTTPS was used for Envato API
    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://api.envato.com');
    });
})->repeat(100);

test('invalid purchase code format returns error', function () {
    $licenseService = app(LicenseService::class);

    // Test various invalid formats
    $invalidCodes = [
        'not-a-uuid',
        '12345678',
        'invalid-format-here',
        '',
        'xxxx-xxxx-xxxx-xxxx',
    ];

    foreach ($invalidCodes as $code) {
        $result = $licenseService->verify($code);

        expect($result->isValid())->toBeFalse();
        expect($result->getError())->toContain('Invalid purchase code format');
    }
})->repeat(100);

test('missing Envato Personal Token returns error', function () {
    // Clear the token
    config(['installer.license.envato_personal_token' => '']);

    // Valid purchase code format
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    $licenseService = app(LicenseService::class);
    $result = $licenseService->verify($purchaseCode);

    expect($result->isValid())->toBeFalse();
    expect($result->getError())->toContain('Envato Personal Token not configured');
})->repeat(100);

test('Envato API 404 response returns invalid purchase code error', function () {
    // Valid format but non-existent purchase code
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    Http::fake([
        'api.envato.com/*' => Http::response([], 404),
    ]);

    $licenseService = app(LicenseService::class);
    $result = $licenseService->verify($purchaseCode);

    expect($result->isValid())->toBeFalse();
    expect($result->getError())->toContain('Invalid purchase code');
    expect($result->getError())->toContain('not found');
})->repeat(100);

test('Envato API 401 response returns authentication error', function () {
    // Valid purchase code format
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    Http::fake([
        'api.envato.com/*' => Http::response([], 401),
    ]);

    $licenseService = app(LicenseService::class);
    $result = $licenseService->verify($purchaseCode);

    expect($result->isValid())->toBeFalse();
    expect($result->getError())->toContain('Invalid Envato Personal Token');
})->repeat(100);

test('license type is correctly extracted from Envato response', function () {
    // Valid purchase code format
    $purchaseCode = sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        mt_rand(0, 0xFFFFFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFF),
        mt_rand(0, 0xFFFFFFFFFFFF)
    );

    $licenseType = ['regular', 'extended'][array_rand(['regular', 'extended'])];

    Http::fake([
        'api.envato.com/*' => Http::response([
            'item' => ['id' => '12345678', 'name' => 'Test Product'],
            'buyer' => 'Test Buyer',
            'license' => $licenseType,
        ], 200),
    ]);

    $licenseService = app(LicenseService::class);
    $result = $licenseService->verify($purchaseCode);

    expect($result->isValid())->toBeTrue();
    expect($result->getLicenseType())->toBe($licenseType);

    if ($licenseType === 'regular') {
        expect($result->isRegularLicense())->toBeTrue();
        expect($result->isExtendedLicense())->toBeFalse();
    } else {
        expect($result->isRegularLicense())->toBeFalse();
        expect($result->isExtendedLicense())->toBeTrue();
    }
})->repeat(100);
