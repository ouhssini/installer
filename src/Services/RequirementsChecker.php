<?php

namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\File;

class RequirementsChecker
{
    /**
     * Check all requirements
     */
    public function check(): array
    {
        $requirements = $this->getAllRequirements();

        $results = [
            'php' => $this->checkPhpVersion($requirements['php']),
            'extensions' => [],
            'directories' => [],
            'all_satisfied' => true,
        ];

        // Check extensions
        foreach ($requirements['extensions'] as $extension) {
            $satisfied = $this->checkExtension($extension);
            $results['extensions'][$extension] = ['satisfied' => $satisfied];
            if (! $satisfied) {
                $results['all_satisfied'] = false;
            }
        }

        // Check directories
        foreach ($requirements['directories'] as $directory) {
            $check = $this->checkDirectory($directory);
            $results['directories'][$directory] = $check;
            if (! $check['satisfied']) {
                $results['all_satisfied'] = false;
            }
        }

        if (! $results['php']['satisfied']) {
            $results['all_satisfied'] = false;
        }

        return $results;
    }

    /**
     * Check PHP version
     */
    public function checkPhpVersion(string $required): array
    {
        $current = PHP_VERSION;
        $satisfied = version_compare($current, $required, '>=');

        return [
            'required' => $required,
            'current' => $current,
            'satisfied' => $satisfied,
        ];
    }

    /**
     * Check if a PHP extension is loaded
     */
    public function checkExtension(string $extension): bool
    {
        return extension_loaded($extension);
    }

    /**
     * Check if a directory exists and is writable
     */
    public function checkDirectory(string $path): array
    {
        $fullPath = base_path($path);
        $exists = File::exists($fullPath);
        $writable = $exists && File::isWritable($fullPath);

        return [
            'exists' => $exists,
            'writable' => $writable,
            'satisfied' => $exists && $writable,
            'path' => $fullPath,
        ];
    }

    /**
     * Get all requirements from config
     */
    public function getAllRequirements(): array
    {
        return [
            'php' => config('installer.requirements.php', '8.2'),
            'extensions' => config('installer.requirements.extensions', [
                'pdo',
                'openssl',
                'mbstring',
                'tokenizer',
                'json',
                'curl',
                'xml',
                'ctype',
                'fileinfo',
            ]),
            'directories' => config('installer.requirements.directories', [
                'storage',
                'storage/app',
                'storage/framework',
                'storage/logs',
                'bootstrap/cache',
            ]),
        ];
    }
}
