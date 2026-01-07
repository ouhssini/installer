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
        $this->installer->completeStep(1);
        $this->installer->setCurrentStep(2);

        return redirect()->route('installer.app-config');
    }
}
