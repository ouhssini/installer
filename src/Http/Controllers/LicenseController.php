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

            if (!$result->isValid()) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'purchase_code' => $result->getError(),
                    ]);
            }
        }

        $this->installer->completeStep(4);
        $this->installer->setCurrentStep(5);

        return redirect()->route('installer.admin');
    }
}
