<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use SoftCortex\Installer\Services\InstallerService;

beforeEach(function () {
    // Drop settings table if it exists
    Schema::dropIfExists('settings');
});

afterEach(function () {
    // Clean up
    Schema::dropIfExists('settings');
});

test('isInstalled returns false when settings table does not exist', function () {
    $installer = app(InstallerService::class);

    // Ensure table doesn't exist
    expect(Schema::hasTable('settings'))->toBeFalse();

    // Should return false without throwing exception
    expect($installer->isInstalled())->toBeFalse();
});

test('getSetting returns default when settings table does not exist', function () {
    $installer = app(InstallerService::class);

    // Ensure table doesn't exist
    expect(Schema::hasTable('settings'))->toBeFalse();

    // Should return default without throwing exception
    $result = $installer->getSetting('test_key', 'default_value');
    expect($result)->toBe('default_value');
});

test('hasSetting returns false when settings table does not exist', function () {
    $installer = app(InstallerService::class);

    // Ensure table doesn't exist
    expect(Schema::hasTable('settings'))->toBeFalse();

    // Should return false without throwing exception
    expect($installer->hasSetting('test_key'))->toBeFalse();
});

test('isInstalled works correctly after table is created', function () {
    $installer = app(InstallerService::class);

    // Initially no table
    expect($installer->isInstalled())->toBeFalse();

    // Create settings table
    Schema::create('settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->timestamps();
    });

    // Still not installed (no data)
    expect($installer->isInstalled())->toBeFalse();

    // Mark as installed
    $installer->markAsInstalled();

    // Now should be installed
    expect($installer->isInstalled())->toBeTrue();
});

test('getCurrentStep returns default when table does not exist', function () {
    $installer = app(InstallerService::class);

    // Ensure table doesn't exist
    expect(Schema::hasTable('settings'))->toBeFalse();

    // Should return default step (1) without throwing exception
    expect($installer->getCurrentStep())->toBe(1);
});
