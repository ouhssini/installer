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
    // Clean up
    $installedFile = storage_path('app/.installed');
    $settingsFile = storage_path('app/installer-settings.json');

    if (File::exists($installedFile)) {
        File::delete($installedFile);
    }

    if (File::exists($settingsFile)) {
        File::delete($settingsFile);
    }
});

test('isInstalled returns false when not installed', function () {
    $installer = app(InstallerService::class);

    expect($installer->isInstalled())->toBeFalse();
});

test('isInstalled returns true after marking as installed', function () {
    $installer = app(InstallerService::class);

    $installer->markAsInstalled();

    expect($installer->isInstalled())->toBeTrue();
});

test('markAsNotInstalled removes installation marker', function () {
    $installer = app(InstallerService::class);

    // Mark as installed first
    $installer->markAsInstalled();
    expect($installer->isInstalled())->toBeTrue();

    // Mark as not installed
    $installer->markAsNotInstalled();
    expect($installer->isInstalled())->toBeFalse();
});

test('getSetting returns default when setting does not exist', function () {
    $installer = app(InstallerService::class);

    $value = $installer->getSetting('nonexistent_key', 'default_value');
    expect($value)->toBe('default_value');
});

test('getSetting returns correct value after setSetting', function () {
    $installer = app(InstallerService::class);

    $installer->setSetting('test_key', 'test_value');
    $value = $installer->getSetting('test_key');

    expect($value)->toBe('test_value');
});

test('hasSetting returns false when setting does not exist', function () {
    $installer = app(InstallerService::class);

    expect($installer->hasSetting('nonexistent_key'))->toBeFalse();
});

test('hasSetting returns true after setting is created', function () {
    $installer = app(InstallerService::class);

    $installer->setSetting('test_key', 'test_value');

    expect($installer->hasSetting('test_key'))->toBeTrue();
});

test('getCurrentStep returns default step when not set', function () {
    $installer = app(InstallerService::class);

    expect($installer->getCurrentStep())->toBe(1);
});

test('setCurrentStep updates the current step', function () {
    $installer = app(InstallerService::class);

    $installer->setCurrentStep(3);

    expect($installer->getCurrentStep())->toBe(3);
});

test('completeStep marks step as completed', function () {
    $installer = app(InstallerService::class);

    $installer->completeStep(2);

    expect($installer->isStepCompleted(2))->toBeTrue();
    expect($installer->isStepCompleted(3))->toBeFalse();
});

test('clearInstallerData removes all installer files', function () {
    $installer = app(InstallerService::class);

    // Create some data
    $installer->markAsInstalled();
    $installer->setSetting('test_key', 'test_value');

    // Verify files exist
    expect(File::exists(storage_path('app/.installed')))->toBeTrue();
    expect(File::exists(storage_path('app/installer-settings.json')))->toBeTrue();

    // Clear data
    $installer->clearInstallerData();

    // Verify files are deleted
    expect(File::exists(storage_path('app/.installed')))->toBeFalse();
    expect(File::exists(storage_path('app/installer-settings.json')))->toBeFalse();
});
