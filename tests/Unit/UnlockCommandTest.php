<?php

use SoftCortex\Installer\Services\InstallerService;
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

test('unlock command resets installation state', function () {
    $installer = app(InstallerService::class);
    
    // Mark as installed
    $installer->markAsInstalled();
    expect($installer->isInstalled())->toBeTrue();
    
    // Run unlock command with --force flag
    $this->artisan('installer:unlock', ['--force' => true])
        ->assertSuccessful();
    
    // Verify installation state is reset
    expect($installer->isInstalled())->toBeFalse();
});

test('unlock command displays success message', function () {
    $this->artisan('installer:unlock', ['--force' => true])
        ->expectsOutput('Installation state has been reset.')
        ->assertSuccessful();
});

test('unlock command without force flag prompts for confirmation', function () {
    $this->artisan('installer:unlock')
        ->expectsConfirmation('This will reset the installation state. Are you sure?', 'no')
        ->expectsOutput('Operation cancelled.')
        ->assertFailed();
});

test('unlock command with force flag skips confirmation', function () {
    $this->artisan('installer:unlock', ['--force' => true])
        ->doesntExpectOutput('Are you sure?')
        ->assertSuccessful();
});
