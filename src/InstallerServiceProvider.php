<?php

namespace SoftCortex\Installer;

use SoftCortex\Installer\Commands\InstallerCommand;
use SoftCortex\Installer\Http\Middleware\EnsureInstalled;
use SoftCortex\Installer\Http\Middleware\RedirectIfInstalled;
use SoftCortex\Installer\Services\DatabaseManager;
use SoftCortex\Installer\Services\EnvironmentManager;
use SoftCortex\Installer\Services\InstallerService;
use SoftCortex\Installer\Services\LicenseService;
use SoftCortex\Installer\Services\RequirementsChecker;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class InstallerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('installer')
            ->hasConfigFile('installer')
            ->hasViews('installer')
            ->hasRoute('installer')
            ->hasMigration('create_settings_table')
            ->hasCommand(InstallerCommand::class);
    }

    public function packageRegistered(): void
    {
        // Register services
        $this->app->singleton(EnvironmentManager::class);
        $this->app->singleton(DatabaseManager::class);
        $this->app->singleton(RequirementsChecker::class);
        $this->app->singleton(InstallerService::class);
        $this->app->singleton(LicenseService::class);
    }

    public function packageBooted(): void
    {
        // Register middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('installer.redirect', RedirectIfInstalled::class);
        $router->aliasMiddleware('installer.ensure', EnsureInstalled::class);

        // Register global middleware to protect all routes
        $router->pushMiddlewareToGroup('web', EnsureInstalled::class);
    }
}
