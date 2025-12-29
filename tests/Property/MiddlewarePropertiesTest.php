<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use SoftCortex\Installer\Http\Middleware\EnsureInstalled;
use SoftCortex\Installer\Http\Middleware\RedirectIfInstalled;
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

    // Set up test routes
    Route::get('/test-route', function () {
        return 'test response';
    })->name('test.route');

    Route::get('/install/test', function () {
        return 'installer response';
    })->name('installer.test');

    Route::get('/install', function () {
        return 'installer welcome';
    })->name('installer.welcome');

    Route::get('/dashboard', function () {
        return 'dashboard';
    })->name('dashboard');
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

// Feature: envato-installer-wizard, Property 2: Middleware Access Control - Not Installed
test('non-installer routes redirect to installer when not installed', function () {
    $installer = app(InstallerService::class);
    $installer->markAsNotInstalled();

    $middleware = new EnsureInstalled($installer);
    $request = Request::create('/test-route', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response('should not reach here');
    });

    expect($response->isRedirect())->toBeTrue();
    expect($response->getTargetUrl())->toContain('install');
})->repeat(100);

// Feature: envato-installer-wizard, Property 3: Middleware Access Control - Installer Routes Exempt
test('installer routes are accessible when not installed', function () {
    $installer = app(InstallerService::class);
    $installer->markAsNotInstalled();

    $middleware = new EnsureInstalled($installer);
    $request = Request::create('/install/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response('installer accessible');
    });

    expect($response->getContent())->toBe('installer accessible');
})->repeat(100);

test('all installer route patterns are exempt from redirect', function () {
    $installer = app(InstallerService::class);
    $installer->markAsNotInstalled();

    $middleware = new EnsureInstalled($installer);

    $installerPaths = [
        '/install',
        '/install/welcome',
        '/install/requirements',
        '/install/database',
        '/install/license',
        '/install/admin',
        '/install/finalize',
    ];

    foreach ($installerPaths as $path) {
        $request = Request::create($path, 'GET');

        $response = $middleware->handle($request, function ($req) {
            return response('accessible');
        });

        expect($response->getContent())->toBe('accessible');
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 4: Middleware Access Control - Installed
test('installer routes redirect to dashboard when installed', function () {
    $installer = app(InstallerService::class);
    $installer->markAsInstalled();

    $middleware = new RedirectIfInstalled($installer);
    $request = Request::create('/install/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response('should not reach here');
    });

    expect($response->isRedirect())->toBeTrue();
})->repeat(100);

test('application routes are accessible when installed', function () {
    $installer = app(InstallerService::class);
    $installer->markAsInstalled();

    $middleware = new EnsureInstalled($installer);
    $request = Request::create('/test-route', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response('app accessible');
    });

    expect($response->getContent())->toBe('app accessible');
})->repeat(100);

test('middleware respects installation state changes', function () {
    $installer = app(InstallerService::class);
    $ensureMiddleware = new EnsureInstalled($installer);
    $redirectMiddleware = new RedirectIfInstalled($installer);

    // Not installed state
    $installer->markAsNotInstalled();

    $appRequest = Request::create('/test-route', 'GET');
    $response = $ensureMiddleware->handle($appRequest, fn ($req) => response('app'));
    expect($response->isRedirect())->toBeTrue();

    $installerRequest = Request::create('/install/test', 'GET');
    $response = $redirectMiddleware->handle($installerRequest, fn ($req) => response('installer'));
    expect($response->getContent())->toBe('installer');

    // Installed state
    $installer->markAsInstalled();

    $appRequest = Request::create('/test-route', 'GET');
    $response = $ensureMiddleware->handle($appRequest, fn ($req) => response('app'));
    expect($response->getContent())->toBe('app');

    $installerRequest = Request::create('/install/test', 'GET');
    $response = $redirectMiddleware->handle($installerRequest, fn ($req) => response('installer'));
    expect($response->isRedirect())->toBeTrue();
})->repeat(100);
