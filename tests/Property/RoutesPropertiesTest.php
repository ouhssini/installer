<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
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

// Feature: envato-installer-wizard, Property 5: Step Progression
test('completing a step allows navigation to next step', function () {
    $installer = app(InstallerService::class);

    // Complete step 1
    $installer->completeStep(1);
    expect($installer->isStepCompleted(1))->toBeTrue();

    // Set current step to 2
    $installer->setCurrentStep(2);
    expect($installer->getCurrentStep())->toBe(2);

    // Complete step 2
    $installer->completeStep(2);
    expect($installer->isStepCompleted(2))->toBeTrue();

    // Verify step 1 is still completed
    expect($installer->isStepCompleted(1))->toBeTrue();
})->repeat(100);

// Feature: envato-installer-wizard, Property 28: Route Prefix Consistency
test('all installer routes have install prefix', function () {
    $routes = Route::getRoutes();
    $installerRoutes = [];

    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && str_starts_with($name, 'installer.')) {
            $installerRoutes[] = [
                'name' => $name,
                'uri' => $route->uri(),
            ];
        }
    }

    foreach ($installerRoutes as $route) {
        expect($route['uri'])->toStartWith('install');
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 29: Named Routes
test('all installer routes have names assigned', function () {
    $routes = Route::getRoutes();
    $installerUris = [
        'install',
        'install/requirements',
        'install/database',
        'install/license',
        'install/admin',
        'install/finalize',
    ];

    foreach ($installerUris as $uri) {
        $route = $routes->getByAction("SoftCortex\Installer\Http\Controllers\\*@index")
                 ?? $routes->match(request()->create($uri, 'GET'));

        if ($route) {
            expect($route->getName())->not->toBeNull();
            expect($route->getName())->toStartWith('installer.');
        }
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 30: Middleware Application
test('all installer routes have RedirectIfInstalled middleware applied', function () {
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && str_starts_with($name, 'installer.')) {
            $middleware = $route->middleware();

            // Check if installer.redirect middleware is applied
            $hasRedirectMiddleware = in_array('installer.redirect', $middleware) ||
                                    in_array('SoftCortex\Installer\Http\Middleware\RedirectIfInstalled', $middleware);

            expect($hasRedirectMiddleware)->toBeTrue();
        }
    }
})->repeat(100);

test('step progression is sequential', function () {
    $installer = app(InstallerService::class);

    // Complete steps in order
    for ($step = 1; $step <= 6; $step++) {
        $installer->completeStep($step);
        $installer->setCurrentStep($step + 1);

        expect($installer->isStepCompleted($step))->toBeTrue();

        // Verify all previous steps are still completed
        for ($prevStep = 1; $prevStep < $step; $prevStep++) {
            expect($installer->isStepCompleted($prevStep))->toBeTrue();
        }
    }
})->repeat(100);

test('current step can be retrieved and updated', function () {
    $installer = app(InstallerService::class);

    $steps = [1, 2, 3, 4, 5, 6];

    foreach ($steps as $step) {
        $installer->setCurrentStep($step);
        expect($installer->getCurrentStep())->toBe($step);
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 20: Service Provider Bootstrapping
test('service provider registers routes middleware and views', function () {
    // Check routes are registered
    $routes = Route::getRoutes();
    $hasInstallerRoutes = false;

    foreach ($routes as $route) {
        if ($route->getName() && str_starts_with($route->getName(), 'installer.')) {
            $hasInstallerRoutes = true;
            break;
        }
    }

    expect($hasInstallerRoutes)->toBeTrue();

    // Check middleware is registered
    $router = app('router');
    $middleware = $router->getMiddleware();
    expect($middleware)->toHaveKey('installer.redirect');

    // Check views are available
    expect(view()->exists('installer::welcome'))->toBeTrue();
})->repeat(100);
