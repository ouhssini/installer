<?php
namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InstallerService
{
    private string $installedFilePath;

    private string $settingsFilePath;

    public function __construct(
        private DatabaseManager $database,
        private EnvironmentManager $environment
    ) {
        $this->installedFilePath = storage_path('app/.installed');
        $this->settingsFilePath  = storage_path('app/installer-settings.json');
    }

    /**
     * Check if the application is installed
     */
    public function isInstalled(): bool
    {
        return File::exists($this->installedFilePath);
    }

    /**
     * Mark the application as installed
     */
    public function markAsInstalled(): void
    {
        File::put($this->installedFilePath, json_encode([
            'installed'    => true,
            'installed_at' => now()->toDateTimeString(),
        ]));
    }

    /**
     * Mark the application as not installed
     */
    public function markAsNotInstalled(): void
    {
        if (File::exists($this->installedFilePath)) {
            File::delete($this->installedFilePath);
        }
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->loadSettings();

        return $settings[$key] ?? $default;
    }

    /**
     * Set a setting value
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings       = $this->loadSettings();
        $settings[$key] = $value;
        $this->saveSettings($settings);
    }

    /**
     * Check if a setting exists
     */
    public function hasSetting(string $key): bool
    {
        $settings = $this->loadSettings();

        return isset($settings[$key]);
    }

    /**
     * Load settings from file
     */
    private function loadSettings(): array
    {
        if (! File::exists($this->settingsFilePath)) {
            return [];
        }

        $content = File::get($this->settingsFilePath);

        return json_decode($content, true) ?? [];
    }

    /**
     * Save settings to file
     */
    private function saveSettings(array $settings): void
    {
        File::put($this->settingsFilePath, json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Get the current installation step
     */
    public function getCurrentStep(): int
    {
        return (int) $this->getSetting('current_step', 1);
    }

    /**
     * Set the current installation step
     */
    public function setCurrentStep(int $step): void
    {
        $this->setSetting('current_step', $step);
    }

    /**
     * Mark a step as completed
     */
    public function completeStep(int $step): void
    {
        $completedSteps = $this->getCompletedSteps();
        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            $this->setSetting('completed_steps', $completedSteps);
        }
    }

    /**
     * Check if a step is completed
     */
    public function isStepCompleted(int $step): bool
    {
        return in_array($step, $this->getCompletedSteps());
    }

    /**
     * Get all completed steps
     */
    public function getCompletedSteps(): array
    {
        return $this->getSetting('completed_steps', []);
    }

    /**
     * Check if a step is accessible (completed or next available)
     */
    public function isStepAccessible(int $step): bool
    {
        return $this->isStepCompleted($step) || $step === $this->getNextAvailableStep();
    }

    /**
     * Get the next available step (max completed + 1)
     */
    public function getNextAvailableStep(): int
    {
        $completed = $this->getCompletedSteps();
        return empty($completed) ? 1 : max($completed) + 1;
    }

    /**
     * Get route name for a step number
     */
    public function getStepRoute(int $step): string
    {
        $routes = [
            1 => 'installer.welcome',
            2 => 'installer.app-config',
            3 => 'installer.requirements',
            4 => 'installer.database',
            5 => 'installer.license',
            6 => 'installer.admin',
            7 => 'installer.finalize',
        ];

        return $routes[$step] ?? 'installer.welcome';
    }

    /**
     * Check if database settings table is available
     */
    private function isDatabaseAvailable(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync installation data to database settings table
     */
    private function syncToDatabase(): void
    {
        if (! $this->isDatabaseAvailable()) {
            return;
        }

        try {
            // Check if required columns exist
            $hasCategory = Schema::hasColumn('settings', 'category');
            $hasChangeable = Schema::hasColumn('settings', 'changeable');

            // Build data array based on available columns
            $appInstalledData = [
                'value' => 'true',
                'updated_at' => now(),
            ];

            if ($hasCategory) {
                $appInstalledData['category'] = 'Installer';
            }

            if ($hasChangeable) {
                $appInstalledData['changeable'] = false;
            }

            // Only set created_at if inserting new record
            if (! DB::table('settings')->where('key', 'app_installed')->exists()) {
                $appInstalledData['created_at'] = now();
            }

            DB::table('settings')->updateOrInsert(
                ['key' => 'app_installed'],
                $appInstalledData
            );

            // Sync installation date
            $installationDate = $this->getSetting('installation_date');
            if ($installationDate) {
                $installationDateData = [
                    'value' => $installationDate,
                    'updated_at' => now(),
                ];

                if ($hasCategory) {
                    $installationDateData['category'] = 'Installer';
                }

                if ($hasChangeable) {
                    $installationDateData['changeable'] = false;
                }

                // Only set created_at if inserting new record
                if (! DB::table('settings')->where('key', 'installation_date')->exists()) {
                    $installationDateData['created_at'] = now();
                }

                DB::table('settings')->updateOrInsert(
                    ['key' => 'installation_date'],
                    $installationDateData
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to sync installation data to database', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Finalize the installation
     */
    public function finalize(): void
    {
        $this->markAsInstalled();
        $this->setSetting('installation_date', now()->toDateTimeString());

        // Sync to database if available
        $this->syncToDatabase();

        // Switch to database drivers for session, cache, and queue
        $this->switchToDatabaseDrivers();

        // Clear all caches (wrapped in try-catch for testing environments)
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
        } catch (\Exception $e) {
            // Silently fail in testing environments where cache tables may not exist
        }
    }

    /**
     * Clear all installer data
     */
    public function clearInstallerData(): void
    {
        if (File::exists($this->installedFilePath)) {
            File::delete($this->installedFilePath);
        }

        if (File::exists($this->settingsFilePath)) {
            File::delete($this->settingsFilePath);
        }

        //  clear database settings if available
        if ($this->isDatabaseAvailable()) {
            try {
                DB::table('settings')->whereIn('key', [
                    'app_installed',
                    'installation_date',
                ])->delete();
            } catch (\Exception $e) {
                // Silently fail
            }
        }

        // Clear all caches (wrapped in try-catch for testing environments)
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
        } catch (\Exception $e) {
            // Silently fail in testing environments where cache tables may not exist
        }
    }

    /**
     * Switch environment to use database drivers
     */
    private function switchToDatabaseDrivers(): void
    {
        try {
            $this->environment->setMultiple([
                'SESSION_DRIVER'   => 'database',
                'CACHE_STORE'      => 'database',
                'QUEUE_CONNECTION' => 'database',
            ]);

            Log::info('Switched to database drivers for session, cache, and queue');
        } catch (\Exception $e) {
            Log::warning('Failed to switch to database drivers', [
                'error' => $e->getMessage(),
            ]);
        }
    }

}
