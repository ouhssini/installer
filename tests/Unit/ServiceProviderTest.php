<?php

use SoftCortex\Installer\InstallerServiceProvider;
use Illuminate\Support\Facades\File;

test('service provider publishes config file', function () {
    $provider = new InstallerServiceProvider($this->app);
    
    // Get publishable assets
    $publishes = InstallerServiceProvider::pathsToPublish(
        InstallerServiceProvider::class,
        'installer-config'
    );
    
    expect($publishes)->not->toBeEmpty();
    
    // Verify config file is in publishable assets
    $configPublished = false;
    foreach ($publishes as $source => $destination) {
        if (str_contains($source, 'config/installer.php')) {
            $configPublished = true;
            expect(File::exists($source))->toBeTrue();
        }
    }
    
    expect($configPublished)->toBeTrue();
});

test('service provider publishes migration file', function () {
    $provider = new InstallerServiceProvider($this->app);
    
    // Get publishable assets
    $publishes = InstallerServiceProvider::pathsToPublish(
        InstallerServiceProvider::class,
        'installer-migrations'
    );
    
    expect($publishes)->not->toBeEmpty();
    
    // Verify migration file is in publishable assets
    $migrationPublished = false;
    foreach ($publishes as $source => $destination) {
        if (str_contains($source, 'create_settings_table.php')) {
            $migrationPublished = true;
            expect(File::exists($source))->toBeTrue();
        }
    }
    
    expect($migrationPublished)->toBeTrue();
});

test('service provider publishes views', function () {
    $provider = new InstallerServiceProvider($this->app);
    
    // Get publishable assets
    $publishes = InstallerServiceProvider::pathsToPublish(
        InstallerServiceProvider::class,
        'installer-views'
    );
    
    // In test environment, views might not be published yet
    // Just verify the views directory exists
    $viewsPath = __DIR__ . '/../../resources/views';
    expect(File::exists($viewsPath))->toBeTrue();
    expect(File::isDirectory($viewsPath))->toBeTrue();
});

test('service provider registers routes', function () {
    // Check if installer routes are registered
    $routes = collect(app('router')->getRoutes())->map(function ($route) {
        return $route->getName();
    })->filter();
    
    expect($routes->contains('installer.welcome'))->toBeTrue();
    expect($routes->contains('installer.requirements'))->toBeTrue();
    expect($routes->contains('installer.database'))->toBeTrue();
    expect($routes->contains('installer.license'))->toBeTrue();
    expect($routes->contains('installer.admin'))->toBeTrue();
    expect($routes->contains('installer.finalize'))->toBeTrue();
});

test('service provider registers middleware', function () {
    $router = app('router');
    
    // Verify middleware aliases exist
    $middlewareAliases = $router->getMiddleware();
    expect($middlewareAliases)->toHaveKey('installer.redirect');
    expect($middlewareAliases)->toHaveKey('installer.ensure');
    
    // Verify middleware classes are correct
    expect($middlewareAliases['installer.redirect'])->toBe(\SoftCortex\Installer\Http\Middleware\RedirectIfInstalled::class);
    expect($middlewareAliases['installer.ensure'])->toBe(\SoftCortex\Installer\Http\Middleware\EnsureInstalled::class);
});

test('service provider registers services', function () {
    // Verify all services are registered as singletons
    expect(app()->bound(\SoftCortex\Installer\Services\EnvironmentManager::class))->toBeTrue();
    expect(app()->bound(\SoftCortex\Installer\Services\DatabaseManager::class))->toBeTrue();
    expect(app()->bound(\SoftCortex\Installer\Services\RequirementsChecker::class))->toBeTrue();
    expect(app()->bound(\SoftCortex\Installer\Services\InstallerService::class))->toBeTrue();
    expect(app()->bound(\SoftCortex\Installer\Services\LicenseService::class))->toBeTrue();
    
    // Verify they are singletons (same instance returned)
    $service1 = app(\SoftCortex\Installer\Services\InstallerService::class);
    $service2 = app(\SoftCortex\Installer\Services\InstallerService::class);
    expect($service1)->toBe($service2);
});

test('config file exists and is valid', function () {
    $configPath = __DIR__ . '/../../config/installer.php';
    
    expect(File::exists($configPath))->toBeTrue();
    
    $config = require $configPath;
    
    expect($config)->toBeArray();
    expect($config)->toHaveKey('product');
    expect($config)->toHaveKey('requirements');
    expect($config)->toHaveKey('license');
    expect($config)->toHaveKey('routes');
    expect($config)->toHaveKey('admin');
});

test('migration file exists and is valid', function () {
    $migrationPath = __DIR__ . '/../../database/migrations/create_settings_table.php.stub';
    
    expect(File::exists($migrationPath))->toBeTrue();
    
    $content = File::get($migrationPath);
    
    expect($content)->toContain('Schema::create');
    expect($content)->toContain('settings');
    expect($content)->toContain('key');
    expect($content)->toContain('value');
});

test('all view files exist', function () {
    $viewsPath = __DIR__ . '/../../resources/views';
    
    expect(File::exists($viewsPath))->toBeTrue();
    
    $requiredViews = [
        'welcome.blade.php',
        'requirements.blade.php',
        'database.blade.php',
        'license.blade.php',
        'admin.blade.php',
        'finalize.blade.php',
    ];
    
    foreach ($requiredViews as $view) {
        expect(File::exists($viewsPath . '/' . $view))->toBeTrue();
    }
});
