<?php

use Illuminate\Support\Facades\File;
use SoftCortex\Installer\Services\InstallerService;

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

// Feature: envato-installer-wizard, Property 1: Installation State Persistence
test('installation completion sets app_installed to true and subsequent checks return installed status', function () {
    $installer = app(InstallerService::class);

    // Initially should not be installed
    expect($installer->isInstalled())->toBeFalse();

    // Mark as installed
    $installer->markAsInstalled();

    // Should now be installed
    expect($installer->isInstalled())->toBeTrue();

    // Verify file exists
    expect(File::exists(storage_path('app/.installed')))->toBeTrue();

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

    // Verify file is deleted
    expect(File::exists(storage_path('app/.installed')))->toBeFalse();
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

    // Clear installer data (simulating unlock command)
    $installer->clearInstallerData();

    // Verify state is reset
    expect($installer->isInstalled())->toBeFalse();

    // Verify files are deleted
    expect(File::exists(storage_path('app/.installed')))->toBeFalse();
    expect(File::exists(storage_path('app/installer-settings.json')))->toBeFalse();
})->repeat(100);
