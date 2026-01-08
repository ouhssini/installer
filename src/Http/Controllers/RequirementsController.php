<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SoftCortex\Installer\Services\InstallerService;
use SoftCortex\Installer\Services\RequirementsChecker;

class RequirementsController extends Controller
{
    public function __construct(
        private InstallerService $installer,
        private RequirementsChecker $checker
    ) {}

    public function index()
    {
        // Ensure step 2 (App Config) is completed
        if (!$this->installer->isStepCompleted(2)) {
            return redirect()->route('installer.app-config');
        }

        // Allow access if step 3 is completed (editing) OR it's the next step
        if (!$this->installer->isStepAccessible(3)) {
            return redirect()->route($this->installer->getStepRoute($this->installer->getNextAvailableStep()));
        }

        $requirements = $this->checker->check();

        return view('installer::requirements', [
            'requirements' => $requirements,
        ]);
    }

    public function check(Request $request)
    {
        $requirements = $this->checker->check();

        return response()->json($requirements);
    }

    public function store(Request $request)
    {
        $requirements = $this->checker->check();

        if (! $requirements['all_satisfied']) {
            return view('installer::requirements', [
                'requirements' => $requirements,
                'error' => 'Please ensure all server requirements are met before continuing.',
            ]);
        }

        $this->installer->completeStep(3);
        $this->installer->setCurrentStep(4);

        return redirect()->route('installer.database');
    }
}
