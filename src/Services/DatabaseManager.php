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

        // Set default database connection to mysql
        Config::set('database.default', 'mysql');

        // Reconnect to database
        DB::purge('mysql');
        DB::reconnect('mysql');

        // Set as default connection for subsequent operations
        DB::setDefaultConnection('mysql');
    }

    /**
     * Run database migrations
     */
    public function runMigrations(bool $runSeeders = false): array
    {
        $output = [];

        try {
            // Only create settings table programmatically if migration doesn't exist
            // This prevents conflict when users publish the migration
            if (!$this->migrationExists('create_settings_table')) {
                $this->createSettingsTable();
            }

            // Run all migrations
            $options = ['--force' => true];
            if ($runSeeders) {
                $options['--seed'] = true;
            }
            
            Artisan::call('migrate', $options);
            $output[] = Artisan::output();

            return [
                'success' => true,
                'output' => $output,
                'seeders_run' => $runSeeders,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => $output,
                'error' => $e->getMessage(),
                'seeders_run' => false,
            ];
        }
    }

    /**
     * Check if a migration file exists in the project
     */
    private function migrationExists(string $migrationName): bool
    {
        $migrationsPath = database_path('migrations');

        if (!is_dir($migrationsPath)) {
            return false;
        }

        $files = scandir($migrationsPath);

        foreach ($files as $file) {
            if (str_contains($file, $migrationName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create the settings table using Laravel Schema builder
     */
    public function createSettingsTable(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('settings')) {
            DB::getSchemaBuilder()->create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('category')->nullable();
                $table->boolean('changeable')->default(true);
                $table->timestamps();
            });
        }
    }
}
