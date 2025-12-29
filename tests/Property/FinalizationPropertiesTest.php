<?php

use SoftCortex\Installer\Services\InstallerService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

beforeEach(function () {
    // Create settings table for tests
    if (!Schema::hasTable('settings')) {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Clean up
    if (Schema::hasTable('settings')) {
        DB::table('settings')->truncate();
    }
});

// Feature: envato-installer-wizard, Property 16: Installation Finalization
test('finalization sets app_installed to true and clears caches', function () {
    $installer = app(InstallerService::class);
    
    // Ensure not installed initially
    $installer->markAsNotInstalled();
    expect($installer->isInstalled())->toBeFalse();
    
    // Finalize installation
    $installer->finalize();
    
    // Verify app_installed is set to true
    expect($installer->isInstalled())->toBeTrue();
    
    // Verify installation date is set
    $installationDate = $installer->getSetting('installation_date');
    expect($installationDate)->not->toBeNull();
    
    // Verify setting is persisted in database
    $setting = DB::table('settings')->where('key', 'app_installed')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('true');
})->repeat(100);

test('finalization can be called multiple times safely', function () {
    $installer = app(InstallerService::class);
    
    // First finalization
    $installer->finalize();
    expect($installer->isInstalled())->toBeTrue();
    $firstDate = $installer->getSetting('installation_date');
    
    // Second finalization (should not error)
    $installer->finalize();
    expect($installer->isInstalled())->toBeTrue();
    
    // Installation date should be updated
    $secondDate = $installer->getSetting('installation_date');
    expect($secondDate)->not->toBeNull();
})->repeat(100);

test('finalization marks all steps as complete', function () {
    $installer = app(InstallerService::class);
    
    // Complete some steps
    $installer->completeStep(1);
    $installer->completeStep(2);
    $installer->completeStep(3);
    
    // Finalize
    $installer->finalize();
    
    // Verify installation is complete
    expect($installer->isInstalled())->toBeTrue();
    
    // Verify completed steps are preserved
    expect($installer->isStepCompleted(1))->toBeTrue();
    expect($installer->isStepCompleted(2))->toBeTrue();
    expect($installer->isStepCompleted(3))->toBeTrue();
})->repeat(100);

test('installation state persists after finalization', function () {
    $installer = app(InstallerService::class);
    
    // Finalize
    $installer->finalize();
    expect($installer->isInstalled())->toBeTrue();
    
    // Create new instance
    $newInstaller = app(InstallerService::class);
    expect($newInstaller->isInstalled())->toBeTrue();
})->repeat(100);
