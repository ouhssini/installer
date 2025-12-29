<?php

namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class InstallerService
{
    public function __construct(
        private DatabaseManager $database,
        private EnvironmentManager $environment
    ) {}

    /**
     * Check if the application is installed
     */
    public function isInstalled(): bool
    {
        try {
            // First check if settings table exists
            if (!$this->settingsTableExists()) {
                return false;
            }
            
            $value = $this->getSetting('app_installed', 'false');

            return $value === 'true';
        } catch (\Exception $e) {
            // If settings table doesn't exist or any error, treat as not installed
            return false;
        }
    }

    /**
     * Mark the application as installed
     */
    public function markAsInstalled(): void
    {
        $this->setSetting('app_installed', 'true');
        $this->setSetting('installation_date', now()->toDateTimeString());
    }

    /**
     * Mark the application as not installed
     */
    public function markAsNotInstalled(): void
    {
        $this->setSetting('app_installed', 'false');
    }

    /**
     * Get a setting value
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            if (!$this->settingsTableExists()) {
                return $default;
            }
            
            $setting = DB::table('settings')->where('key', $key)->first();

            return $setting ? $setting->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Set a setting value
     */
    public function setSetting(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    /**
     * Check if a setting exists
     */
    public function hasSetting(string $key): bool
    {
        try {
            if (!$this->settingsTableExists()) {
                return false;
            }
            
            return DB::table('settings')->where('key', $key)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if the settings table exists
     */
    private function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Exception $e) {
            // If we can't check (e.g., no database connection), return false
            return false;
        }
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
        $this->setSetting('current_step', (string) $step);
    }

    /**
     * Mark a step as completed
     */
    public function completeStep(int $step): void
    {
        $completedSteps = $this->getCompletedSteps();
        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            $this->setSetting('completed_steps', json_encode($completedSteps));
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
        $steps = $this->getSetting('completed_steps', '[]');

        return json_decode($steps, true) ?? [];
    }

    /**
     * Finalize the installation
     */
    public function finalize(): void
    {
        $this->markAsInstalled();

        // Clear all caches
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
    }
}
