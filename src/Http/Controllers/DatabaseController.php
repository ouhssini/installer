<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SoftCortex\Installer\Services\DatabaseManager;
use SoftCortex\Installer\Services\InstallerService;

class DatabaseController extends Controller
{
    public function __construct(
        private InstallerService $installer,
        private DatabaseManager $database
    ) {}

    public function index()
    {
        // Ensure step 3 (Requirements) is completed
        if (!$this->installer->isStepCompleted(3)) {
            return redirect()->route('installer.requirements');
        }

        // Allow access if step 4 is completed (editing) OR it's the next step
        if (!$this->installer->isStepAccessible(4)) {
            return redirect()->route($this->installer->getStepRoute($this->installer->getNextAvailableStep()));
        }

        return view('installer::database');
    }

    public function test(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            $credentials = $request->only([
                'host', 'port', 'database', 'username', 'password',
            ]);

            $this->database->testConnection($credentials);

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful!',
            ]);
        } catch (\PDOException $e) {
            Log::error('Database connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: '.$e->getMessage(),
            ], 422);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'nullable|string',
            'run_seeders' => 'nullable|boolean',
        ]);

        try {
            $credentials = $request->only([
                'host', 'port', 'database', 'username', 'password',
            ]);

            Log::info('Starting database configuration', [
                'host' => $credentials['host'] ?? 'N/A',
                'database' => $credentials['database'] ?? 'N/A',
            ]);

            // Test connection
            $this->database->testConnection($credentials);
            Log::info('Database connection test passed');

            // Write configuration
            $this->database->writeConfiguration($credentials);
            Log::info('Database configuration written');

            // Run migrations with optional seeders
            Log::info('Running database migrations');
            $runSeeders = $request->has('run_seeders') && $request->input('run_seeders') == '1';
            Log::info('Seeder option', ['run_seeders' => $runSeeders]);
            
            $result = $this->database->runMigrations($runSeeders);

            if (! $result['success']) {
                Log::error('Migration failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'output' => $result['output'] ?? [],
                ]);

                return view('installer::database', [
                    'error' => 'Migration failed: '.($result['error'] ?? 'Unknown error'),
                    'credentials' => $credentials,
                ]);
            }

            Log::info('Migrations completed successfully');

            $this->installer->completeStep(4);
            $this->installer->setCurrentStep(5);

            Log::info('Database setup completed successfully');

            return redirect()->route('installer.license');

        } catch (\PDOException $e) {
            Log::error('Database PDO exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('installer::database', [
                'error' => 'Database connection failed: '.$e->getMessage(),
                'credentials' => $credentials ?? [],
            ]);
        } catch (\Exception $e) {
            Log::error('Database configuration exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('installer::database', [
                'error' => 'Configuration failed: '.$e->getMessage(),
                'credentials' => $credentials ?? [],
            ]);

        }
    }
}
