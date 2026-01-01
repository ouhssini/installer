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

            // Test connection
            $this->database->testConnection($credentials);

            // Write configuration
            $this->database->writeConfiguration($credentials);

            // Run migrations
            $result = $this->database->runMigrations();

            if (! $result['success']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'database' => 'Migration failed: '.($result['error'] ?? 'Unknown error'),
                    ]);
            }

            $this->installer->completeStep(4);
            $this->installer->setCurrentStep(5);

            return redirect()->route('installer.license');

        } catch (\PDOException $e) {
            Log::error('Database configuration failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors([
                    'database' => 'Database connection failed. Please check your credentials.',
                ]);
        }
    }
}
