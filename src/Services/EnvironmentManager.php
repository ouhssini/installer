<?php

namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\File;

class EnvironmentManager
{
    private string $envPath;

    private string $envExamplePath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');
    }

    /**
     * Initialize .env file from .env.example
     * Priority: 1) Project's .env.example, 2) Package's .env.example
     */
    public function initializeFromExample(): bool
    {
        // First, try project's .env.example (if developer published and customized it)
        $projectEnvExample = base_path('.env.example');

        // Fall back to package's .env.example
        $packageEnvExample = __DIR__.'/../../.env.example';

        $sourceFile = File::exists($projectEnvExample)
            ? $projectEnvExample
            : $packageEnvExample;

        if (! File::exists($sourceFile)) {
            return false;
        }

        // Copy .env.example to .env (overwrite if exists)
        try {
            File::copy($sourceFile, $this->envPath);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate and set a new application key
     */
    public function generateAppKey(): string
    {
        $key = 'base64:'.base64_encode(random_bytes(32));
        $this->set('APP_KEY', $key);

        return $key;
    }

    /**
     * Check if .env file exists
     */
    public function envFileExists(): bool
    {
        return File::exists($this->envPath);
    }

    /**
     * Get an environment variable value
     */
    public function get(string $key): ?string
    {
        if (! File::exists($this->envPath)) {
            return null;
        }

        $content = File::get($this->envPath);
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Parse key=value
            if (str_contains($line, '=')) {
                [$lineKey, $lineValue] = explode('=', $line, 2);
                $lineKey = trim($lineKey);

                if ($lineKey === $key) {
                    return $this->unquoteValue(trim($lineValue));
                }
            }
        }

        return null;
    }

    /**
     * Set an environment variable value
     */
    public function set(string $key, string $value): void
    {
        $this->setMultiple([$key => $value]);
    }

    /**
     * Set multiple environment variables
     */
    public function setMultiple(array $values): void
    {
        // Create backup
        if (File::exists($this->envPath)) {
            File::copy($this->envPath, $this->envPath.'.backup');
        }

        $content = File::exists($this->envPath) ? File::get($this->envPath) : '';
        $lines = explode("\n", $content);
        $updatedKeys = [];

        // Update existing keys
        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);

            // Skip comments and empty lines
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            // Parse key=value
            if (str_contains($trimmedLine, '=')) {
                [$lineKey] = explode('=', $trimmedLine, 2);
                $lineKey = trim($lineKey);

                if (isset($values[$lineKey])) {
                    $lines[$index] = $lineKey.'='.$this->quoteValue($values[$lineKey]);
                    $updatedKeys[] = $lineKey;
                }
            }
        }

        // Append new keys
        foreach ($values as $key => $value) {
            if (! in_array($key, $updatedKeys)) {
                $lines[] = $key.'='.$this->quoteValue($value);
            }
        }

        // Write back to file
        File::put($this->envPath, implode("\n", $lines));
    }

    /**
     * Check if an environment variable exists
     */
    public function exists(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Quote a value if it contains spaces or special characters
     */
    private function quoteValue(string $value): string
    {
        if (str_contains($value, ' ') || str_contains($value, '#')) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    /**
     * Remove quotes from a value
     */
    private function unquoteValue(string $value): string
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
