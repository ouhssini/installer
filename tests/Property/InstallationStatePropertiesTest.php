<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SoftCortex\Installer\Services\InstallerService;

beforeEach(function () {
    // Create settings table for tests
    if (! Schema::hasTable('settings')) {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Clean up settings table
    if (Schema::hasTable('settings')) {
        DB::table('settings')->truncate();
    }
});

// Feature: envato-installer-wizard, Property 1: Installation State Persistence
test('installation completion sets app_installed to true and subsequent checks return installed status', function () {
    $installer = app(InstallerService::class);

    // Initially should not be installed
    expect($installer->isInstalled())->toBeFalse();

    // Mark as installed
    $installer->markAsInstalled();

    // Should now be installed
    expect($installer->isInstalled())->toBeTrue();

    // Verify in database
    $setting = DB::table('settings')->where('key', 'app_installed')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('true');

    // Create new instance to verify persistence
    $newInstaller = app(InstallerService::class);
    expect($newInstaller->isInstalled())->toBeTrue();
})->repeat(100);

test('marking as not installed sets app_installed to false', function () {
    $installer = app(InstallerService::class);

    // Mark as installed first
    $installer->markAsInstalled();
    expect($installer->isInstalled())->toBeTrue();

    // Mark as not installed
    $installer->markAsNotInstalled();
    expect($installer->isInstalled())->toBeFalse();

    // Verify in database
    $setting = DB::table('settings')->where('key', 'app_installed')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('false');
})->repeat(100);

test('installation state persists across multiple service instances', function () {
    $installer1 = app(InstallerService::class);
    $installer1->markAsInstalled();

    // Create new instance
    $installer2 = app(InstallerService::class);
    expect($installer2->isInstalled())->toBeTrue();

    // Mark as not installed with second instance
    $installer2->markAsNotInstalled();

    // Create third instance
    $installer3 = app(InstallerService::class);
    expect($installer3->isInstalled())->toBeFalse();
})->repeat(100);

test('getSetting returns correct value after setSetting', function () {
    $installer = app(InstallerService::class);

    $key = 'test_key_'.uniqid();
    $value = 'test_value_'.uniqid();

    // Set the setting
    $installer->setSetting($key, $value);

    // Get the setting
    $retrieved = $installer->getSetting($key);
    expect($retrieved)->toBe($value);
})->repeat(100);

test('getSetting returns default when key does not exist', function () {
    $installer = app(InstallerService::class);

    $key = 'nonexistent_key_'.uniqid();
    $default = 'default_value';

    $retrieved = $installer->getSetting($key, $default);
    expect($retrieved)->toBe($default);
})->repeat(100);

// Feature: envato-installer-wizard, Property 18: Unlock Command Resets Installation
test('unlock command sets app_installed to false', function () {
    $installer = app(InstallerService::class);

    // Mark as installed
    $installer->markAsInstalled();
    expect($installer->isInstalled())->toBeTrue();

    // Mark as not installed (simulating unlock command)
    $installer->markAsNotInstalled();

    // Verify state is reset
    expect($installer->isInstalled())->toBeFalse();

    // Verify in database
    $setting = DB::table('settings')->where('key', 'app_installed')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('false');
})->repeat(100);
