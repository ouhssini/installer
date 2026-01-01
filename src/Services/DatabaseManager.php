<?php

namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

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
            $connection = $credentials['connection'] ?? 'mysql';

            if ($connection === 'sqlite') {
                // For SQLite, just check if we can create/access the file
                $database = $credentials['database'] ?? database_path('database.sqlite');

                // Create directory if it doesn't exist
                $dir = dirname($database);
                if (! file_exists($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Create file if it doesn't exist
                if (! file_exists($database)) {
                    touch($database);
                }

                $dsn = "sqlite:{$database}";
                $pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);

                return true;
            }

            // MySQL or PostgreSQL
            $host = $credentials['host'] ?? 'localhost';
            $port = $credentials['port'] ?? ($connection === 'pgsql' ? '5432' : '3306');
            $database = $credentials['database'] ?? '';
            $username = $credentials['username'] ?? '';
            $password = $credentials['password'] ?? '';

            if ($connection === 'pgsql') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            }

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
        $connection = $credentials['connection'] ?? 'mysql';

        if ($connection === 'sqlite') {
            $database = $credentials['database'] ?? database_path('database.sqlite');

            $this->environment->setMultiple([
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $database,
            ]);

            // Set default connection
            Config::set('database.default', 'sqlite');

            // Reload configuration
            Config::set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => $database,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);

            // Purge all connections and reconnect
            DB::disconnect();
            DB::purge('sqlite');
            DB::setDefaultConnection('sqlite');
            DB::reconnect('sqlite');

        } elseif ($connection === 'pgsql') {
            $this->environment->setMultiple([
                'DB_CONNECTION' => 'pgsql',
                'DB_HOST' => $credentials['host'] ?? 'localhost',
                'DB_PORT' => $credentials['port'] ?? '5432',
                'DB_DATABASE' => $credentials['database'] ?? '',
                'DB_USERNAME' => $credentials['username'] ?? '',
                'DB_PASSWORD' => $credentials['password'] ?? '',
            ]);

            // Set default connection
            Config::set('database.default', 'pgsql');

            // Reload configuration
            Config::set('database.connections.pgsql', [
                'driver' => 'pgsql',
                'host' => $credentials['host'] ?? 'localhost',
                'port' => $credentials['port'] ?? '5432',
                'database' => $credentials['database'] ?? '',
                'username' => $credentials['username'] ?? '',
                'password' => $credentials['password'] ?? '',
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);

            // Purge all connections and reconnect
            DB::disconnect();
            DB::purge('pgsql');
            DB::setDefaultConnection('pgsql');
            DB::reconnect('pgsql');

        } else {
            // MySQL
            $this->environment->setMultiple([
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $credentials['host'] ?? 'localhost',
                'DB_PORT' => $credentials['port'] ?? '3306',
                'DB_DATABASE' => $credentials['database'] ?? '',
                'DB_USERNAME' => $credentials['username'] ?? '',
                'DB_PASSWORD' => $credentials['password'] ?? '',
            ]);

            // Set default connection
            Config::set('database.default', 'mysql');

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

            // Purge all connections and reconnect
            DB::disconnect();
            DB::purge('mysql');
            DB::setDefaultConnection('mysql');
            DB::reconnect('mysql');
        }
        
        // Clear config cache
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            // Ignore if fails
        }
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
        $connection = Config::get('database.default');
        
        // Use the specific connection, not the default
        if (! DB::connection($connection)->getSchemaBuilder()->hasTable('settings')) {
            if ($connection === 'sqlite') {
                DB::connection($connection)->statement('
                    CREATE TABLE settings (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        `key` VARCHAR(255) NOT NULL UNIQUE,
                        `value` TEXT NULL,
                        created_at DATETIME NULL,
                        updated_at DATETIME NULL
                    )
                ');
            } elseif ($connection === 'pgsql') {
                DB::connection($connection)->statement('
                    CREATE TABLE settings (
                        id BIGSERIAL PRIMARY KEY,
                        key VARCHAR(255) NOT NULL UNIQUE,
                        value TEXT NULL,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL
                    )
                ');
            } else {
                // MySQL
                DB::connection($connection)->statement('
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
}
