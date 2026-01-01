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
        return view('installer::database');
    }

    public function test(Request $request)
    {
        $connection = $request->input('connection', 'mysql');

        if ($connection === 'sqlite') {
            $request->validate([
                'connection' => 'required|in:sqlite,mysql,pgsql',
                'database' => 'required|string',
            ]);
        } else {
            $request->validate([
                'connection' => 'required|in:sqlite,mysql,pgsql',
                'host' => 'required|string',
                'port' => 'required|numeric',
                'database' => 'required|string',
                'username' => 'required|string',
                'password' => 'nullable|string',
            ]);
        }

        try {
            $credentials = $request->only([
                'connection', 'host', 'port', 'database', 'username', 'password',
            ]);

            $this->database->testConnection($credentials);

            return response()->json([
                'success' => true,
                'message' => 'Database connection successful!',
            ]);
        } catch (\PDOException $e) {
            Log::error('Database connection test failed', [
                'connection' => $connection,
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
        $connection = $request->input('connection', 'mysql');

        if ($connection === 'sqlite') {
            $request->validate([
                'connection' => 'required|in:sqlite,mysql,pgsql',
                'database' => 'required|string',
            ]);
        } else {
            $request->validate([
                'connection' => 'required|in:sqlite,mysql,pgsql',
                'host' => 'required|string',
                'port' => 'required|numeric',
                'database' => 'required|string',
                'username' => 'required|string',
                'password' => 'nullable|string',
            ]);
        }

        try {
            $credentials = $request->only([
                'connection', 'host', 'port', 'database', 'username', 'password',
            ]);

            Log::info('Starting database configuration', [
                'connection' => $connection,
                'host' => $credentials['host'] ?? 'N/A',
                'database' => $credentials['database'] ?? 'N/A',
            ]);

            // Test connection
            $this->database->testConnection($credentials);
            Log::info('Database connection test passed');

            // Write configuration
            $this->database->writeConfiguration($credentials);
            Log::info('Database configuration written');

            // Run migrations
            $result = $this->database->runMigrations();
            Log::info('Migration result', $result);

            if (! $result['success']) {
                Log::error('Migration failed', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'output' => $result['output'] ?? [],
                ]);

                return back()
                    ->withInput()
                    ->withErrors([
                        'database' => 'Migration failed: '.($result['error'] ?? 'Unknown error'),
                    ]);
            }

            $this->installer->completeStep(4);
            $this->installer->setCurrentStep(5);

            Log::info('Database setup completed successfully');

            return redirect()->route('installer.license');

        } catch (\PDOException $e) {
            Log::error('Database PDO exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'database' => 'Database connection failed: '.$e->getMessage(),
                ]);
        } catch (\Exception $e) {
            Log::error('Database configuration exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'database' => 'Configuration failed: '.$e->getMessage(),
                ]);
        }
    }
}
