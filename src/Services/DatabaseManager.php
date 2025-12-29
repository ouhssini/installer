<?php

namespace SoftCortex\Installer\Services;

use PDO;
use PDOException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DatabaseManager
{
    public function __construct(
        private EnvironmentManager $environment
    ) {}

    /**
     * Test database connection with given credentials
     */
    public function testConnection(array $credentials): bool
    {
        try {
            $host = $credentials['host'] ?? 'localhost';
            $port = $credentials['port'] ?? '3306';
            $database = $credentials['database'] ?? '';
            $username = $credentials['username'] ?? '';
            $password = $credentials['password'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return true;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Write database configuration to .env file
     */
    public function writeConfiguration(array $credentials): void
    {
        $this->environment->setMultiple([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $credentials['host'] ?? 'localhost',
            'DB_PORT' => $credentials['port'] ?? '3306',
            'DB_DATABASE' => $credentials['database'] ?? '',
            'DB_USERNAME' => $credentials['username'] ?? '',
            'DB_PASSWORD' => $credentials['password'] ?? '',
        ]);

        // Reload configuration
        Config::set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => $credentials['host'] ?? 'localhost',
            'port' => $credentials['port'] ?? '3306',
            'database' => $credentials['database'] ?? '',
            'username' => $credentials['username'] ?? '',
            'password' => $credentials['password'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Reconnect to database
        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    /**
     * Run database migrations
     */
    public function runMigrations(): array
    {
        $output = [];
        
        try {
            // Create settings table first
            $this->createSettingsTable();
            
            // Run all migrations
            Artisan::call('migrate', ['--force' => true]);
            $output[] = Artisan::output();
            
            return [
                'success' => true,
                'output' => $output,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => $output,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create the settings table
     */
    public function createSettingsTable(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('settings')) {
            DB::statement('
                CREATE TABLE settings (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `key` VARCHAR(255) NOT NULL UNIQUE,
                    `value` TEXT NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
        }
    }
}
