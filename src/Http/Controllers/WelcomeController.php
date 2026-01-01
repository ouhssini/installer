<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use SoftCortex\Installer\Services\EnvironmentManager;
use SoftCortex\Installer\Services\InstallerService;

class WelcomeController extends Controller
{
    public function __construct(
        private InstallerService $installer,
        private EnvironmentManager $environment
    ) {}

    public function index()
    {
        return view('installer::welcome', [
            'product' => config('installer.product'),
        ]);
    }

    public function store(Request $request)
    {
        // Always initialize .env from package's .env.example
        $initialized = $this->environment->initializeFromExample();

        if (! $initialized) {
            return back()->withErrors([
                'env' => 'Failed to create .env file. Please ensure the package is properly installed.',
            ]);
        }

        // Generate new APP_KEY
        $this->environment->generateAppKey();

        // Clear config cache to load new .env values
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('cache:clear');
        } catch (\Exception $e) {
            // Silently continue if cache clear fails
        }

        $this->installer->completeStep(1);
        $this->installer->setCurrentStep(2);

        return redirect()->route('installer.app-config');
    }
}
