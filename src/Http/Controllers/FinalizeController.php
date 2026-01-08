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
        // Ensure step 6 (Admin) is completed
        if (!$this->installer->isStepCompleted(6)) {
            return redirect()->route('installer.admin');
        }

        return view('installer::finalize');
    }

    public function store(Request $request)
    {
        $this->installer->completeStep(7);
        $this->installer->finalize();

        // Auto-login the created admin user
        $userId = $this->installer->getSetting('admin_user_id');
        if ($userId) {
            try {
                $userModel = config('auth.providers.users.model', 'App\\Models\\User');
                $user = $userModel::find($userId);

                if ($user) {
                    \Illuminate\Support\Facades\Auth::login($user);
                }
            } catch (\Exception $e) {
                // Silently fail - user can login manually
            }
        }

        $redirectRoute = config('installer.routes.redirect_after_install', 'dashboard');

        try {
            return redirect()->route($redirectRoute)->with('success', 'Installation completed successfully!');
        } catch (\Exception $e) {
            return redirect('/')->with('success', 'Installation completed successfully!');
        }
    }
}
