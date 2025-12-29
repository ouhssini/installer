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

        if (!$requirements['all_satisfied']) {
            return back()->withErrors([
                'requirements' => 'Please ensure all server requirements are met before continuing.',
            ]);
        }

        $this->installer->completeStep(2);
        $this->installer->setCurrentStep(3);

        return redirect()->route('installer.database');
    }
}
