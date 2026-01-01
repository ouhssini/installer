<?php

namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallerService
{
    private string $installedFilePath;

    private string $settingsFilePath;

    public function __construct(
        private DatabaseManager $database,
        private EnvironmentManager $environment
    ) {
        $this->installedFilePath = storage_path('app/.installed');
        $this->settingsFilePath = storage_path('app/installer-settings.json');
    }

    /**
     * Check if the application is installed
     */
    public function isInstalled(): bool
    {
        // ONLY check file during installation
        // Database check is NOT reliable during installation because:
        // 1. Connection might be changing (SQLite -> MySQL)
        // 2. Settings table might not exist yet
        // 3. We're in the middle of configuring the database
        return File::exists($this->installedFilePath);
    }

    /**
     * Mark the application as installed
     */
    public function markAsInstalled(): void
    {
        File::put($this->installedFilePath, json_encode([
            'installed' => true,
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
        $settings = $this->loadSettings();
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
    private function getCompletedSteps(): array
    {
        return $this->getSetting('completed_steps', []);
    }

    /**
     * Sync installation data to database settings table
     */
    private function syncToDatabase(): void
    {
        try {
            // Check if settings table exists
            if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return;
            }

            // Get all settings from file
            $settings = $this->loadSettings();

            // Sync app_installed
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => 'app_installed'],
                [
                    'value' => 'true',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Sync installation_date
            $installationDate = $settings['installation_date'] ?? now()->toDateTimeString();
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                ['key' => 'installation_date'],
                [
                    'value' => $installationDate,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Sync license data if exists
            if (isset($settings['license_hash'])) {
                \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                    ['key' => 'license_hash'],
                    [
                        'value' => $settings['license_hash'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            if (isset($settings['license_data'])) {
                \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(
                    ['key' => 'license_data'],
                    [
                        'value' => $settings['license_data'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            // Silently fail - file storage is primary during installation
            \Illuminate\Support\Facades\Log::warning('Failed to sync to database', [
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

        // Delete file-based storage after successful database sync
        try {
            // Check if settings table exists
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                // Verify data is in database before deleting files
                $installed = \Illuminate\Support\Facades\DB::table('settings')
                    ->where('key', 'app_installed')
                    ->where('value', 'true')
                    ->exists();

                if ($installed) {
                    // Delete file-based storage
                    if (File::exists($this->installedFilePath)) {
                        File::delete($this->installedFilePath);
                    }

                    if (File::exists($this->settingsFilePath)) {
                        File::delete($this->settingsFilePath);
                    }

                    \Illuminate\Support\Facades\Log::info('Switched to database storage - file storage deleted');
                }
            }
        } catch (\Exception $e) {
            // Keep file storage as fallback
            \Illuminate\Support\Facades\Log::warning('Could not delete file storage', [
                'error' => $e->getMessage(),
            ]);
        }

        // Clear all caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
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

        // Optionally, clear database settings related to installation
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                \Illuminate\Support\Facades\DB::table('settings')->whereIn('key', ['app_installed', 'installation_date'])
                    ->delete();
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
