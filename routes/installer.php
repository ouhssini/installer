<?php

use Illuminate\Support\Facades\Route;
use SoftCortex\Installer\Http\Controllers\AdminController;
use SoftCortex\Installer\Http\Controllers\AppConfigController;
use SoftCortex\Installer\Http\Controllers\DatabaseController;
use SoftCortex\Installer\Http\Controllers\FinalizeController;
use SoftCortex\Installer\Http\Controllers\LicenseController;
use SoftCortex\Installer\Http\Controllers\RequirementsController;
use SoftCortex\Installer\Http\Controllers\WelcomeController;

Route::prefix(config('installer.routes.prefix', 'install'))
    ->middleware('installer')
    ->group(function () {

        // Step 1: Welcome
        Route::get('/', [WelcomeController::class, 'index'])->name('installer.welcome');
        Route::post('/', [WelcomeController::class, 'store'])->name('installer.welcome.store');

        // Step 2: App Configuration
        Route::get('/app-config', [AppConfigController::class, 'index'])->name('installer.app-config');
        Route::post('/app-config', [AppConfigController::class, 'store'])->name('installer.app-config.store');

        // Step 3: Requirements
        Route::get('/requirements', [RequirementsController::class, 'index'])->name('installer.requirements');
        Route::post('/requirements/check', [RequirementsController::class, 'check'])->name('installer.requirements.check');
        Route::post('/requirements', [RequirementsController::class, 'store'])->name('installer.requirements.store');

        // Step 4: Database
        Route::get('/database', [DatabaseController::class, 'index'])->name('installer.database');
        Route::post('/database/test', [DatabaseController::class, 'test'])->name('installer.database.test');
        Route::post('/database', [DatabaseController::class, 'store'])->name('installer.database.store');

        // Step 5: License
        Route::get('/license', [LicenseController::class, 'index'])->name('installer.license');
        Route::post('/license/verify', [LicenseController::class, 'verify'])->name('installer.license.verify');
        Route::post('/license', [LicenseController::class, 'store'])->name('installer.license.store');

        // Step 6: Admin
        Route::get('/admin', [AdminController::class, 'index'])->name('installer.admin');
        Route::post('/admin', [AdminController::class, 'store'])->name('installer.admin.store');

        // Step 7: Finalize
        Route::get('/finalize', [FinalizeController::class, 'index'])->name('installer.finalize');
        Route::post('/finalize', [FinalizeController::class, 'store'])->name('installer.finalize.store');
    });
