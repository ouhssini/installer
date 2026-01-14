<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SoftCortex\Installer\Services\InstallerService;
use SoftCortex\Installer\Services\LicenseService;

class LicenseController extends Controller
{
    public function __construct(
        private InstallerService $installer,
        private LicenseService $license
    ) {}

    public function index()
    {
        // Ensure step 4 (Database) is completed
        if (!$this->installer->isStepCompleted(4)) {
            return redirect()->route('installer.database');
        }

        // Allow access if step 5 is completed (editing) OR it's the next step
        if (!$this->installer->isStepAccessible(5)) {
            return redirect()->route($this->installer->getStepRoute($this->installer->getNextAvailableStep()));
        }

        $licenseEnabled = config('installer.license.enabled', true);

        return view('installer::license', [
            'licenseEnabled' => $licenseEnabled,
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'purchase_code' => 'required|string',
        ]);

        $result = $this->license->verify($request->purchase_code);

        if ($result->isValid()) {
            return response()->json([
                'success' => true,
                'message' => 'License verified successfully!',
                'data' => [
                    'item_name' => $result->itemName,
                    'buyer' => $result->buyerName,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result->getError(),
        ], 422);
    }

    public function store(Request $request)
    {
        $licenseEnabled = config('installer.license.enabled', true);

        if ($licenseEnabled) {
            $request->validate([
                'purchase_code' => 'required|string',
            ]);

            $result = $this->license->verify($request->purchase_code);

            if (! $result->isValid()) {
                return view('installer::license', [
                    'licenseEnabled' => $licenseEnabled,
                    'error' => $result->getError(),
                    'purchase_code' => $request->purchase_code,
                ]);
            }
        } else {
            // Store disabled status
            $this->license->storeDisabledStatus();
        }

        $this->installer->completeStep(5);
        $this->installer->setCurrentStep(6);

        return redirect()->route('installer.smtp');
    }
}
