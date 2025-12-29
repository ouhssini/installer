<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SoftCortex\Installer\Services\InstallerService;

class FinalizeController extends Controller
{
    public function __construct(
        private InstallerService $installer
    ) {}

    public function index()
    {
        return view('installer::finalize');
    }

    public function store(Request $request)
    {
        $this->installer->completeStep(6);
        $this->installer->finalize();

        $redirectRoute = config('installer.routes.redirect_after_install', 'dashboard');

        try {
            return redirect()->route($redirectRoute)->with('success', 'Installation completed successfully!');
        } catch (\Exception $e) {
            return redirect('/')->with('success', 'Installation completed successfully!');
        }
    }
}
