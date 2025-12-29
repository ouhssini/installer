<?php

use SoftCortex\Installer\Services\InstallerService;
use SoftCortex\Installer\Services\EnvironmentManager;
use SoftCortex\Installer\Services\DatabaseManager;
use SoftCortex\Installer\Services\RequirementsChecker;
use SoftCortex\Installer\Services\LicenseService;
use Illuminate\Support\Facades\Route;

test('service provider registers all services', function () {
    expect(app(InstallerService::class))->toBeInstanceOf(InstallerService::class);
    expect(app(EnvironmentManager::class))->toBeInstanceOf(EnvironmentManager::class);
    expect(app(DatabaseManager::class))->toBeInstanceOf(DatabaseManager::class);
    expect(app(RequirementsChecker::class))->toBeInstanceOf(RequirementsChecker::class);
    expect(app(LicenseService::class))->toBeInstanceOf(LicenseService::class);
});

test('service provider registers routes', function () {
    $routes = Route::getRoutes();
    $installerRoutes = [];
    
    foreach ($routes as $route) {
        if ($route->getName() && str_starts_with($route->getName(), 'installer.')) {
            $installerRoutes[] = $route->getName();
        }
    }
    
    expect($installerRoutes)->toContain('installer.welcome');
    expect($installerRoutes)->toContain('installer.requirements');
    expect($installerRoutes)->toContain('installer.database');
    expect($installerRoutes)->toContain('installer.license');
    expect($installerRoutes)->toContain('installer.admin');
    expect($installerRoutes)->toContain('installer.finalize');
});

test('service provider registers views', function () {
    expect(view()->exists('installer::welcome'))->toBeTrue();
    expect(view()->exists('installer::requirements'))->toBeTrue();
    expect(view()->exists('installer::database'))->toBeTrue();
    expect(view()->exists('installer::license'))->toBeTrue();
    expect(view()->exists('installer::admin'))->toBeTrue();
    expect(view()->exists('installer::finalize'))->toBeTrue();
});

test('service provider registers config', function () {
    expect(config('installer'))->toBeArray();
    expect(config('installer.product'))->toBeArray();
    expect(config('installer.requirements'))->toBeArray();
    expect(config('installer.license'))->toBeArray();
});

test('service provider registers middleware aliases', function () {
    $router = app('router');
    $middlewareGroups = $router->getMiddleware();
    
    expect($middlewareGroups)->toHaveKey('installer.redirect');
    expect($middlewareGroups)->toHaveKey('installer.ensure');
});
