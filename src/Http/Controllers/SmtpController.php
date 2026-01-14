<?php

namespace SoftCortex\Installer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use SoftCortex\Installer\Services\EnvironmentManager;
use SoftCortex\Installer\Services\InstallerService;

class SmtpController extends Controller
{
    public function __construct(
        private InstallerService $installer,
        private EnvironmentManager $environment
    ) {}

    public function index()
    {
        // Ensure step 5 (License) is completed
        if (!$this->installer->isStepCompleted(5)) {
            return redirect()->route('installer.license');
        }

        // Allow access if step 6 is completed (editing) OR it's the next step
        if (!$this->installer->isStepAccessible(6)) {
            return redirect()->route($this->installer->getStepRoute($this->installer->getNextAvailableStep()));
        }

        // Get current SMTP values from .env
        $currentConfig = [
            'mail_mailer' => $this->environment->get('MAIL_MAILER') ?? 'smtp',
            'mail_host' => $this->environment->get('MAIL_HOST') ?? '',
            'mail_port' => $this->environment->get('MAIL_PORT') ?? '587',
            'mail_username' => $this->environment->get('MAIL_USERNAME') ?? '',
            'mail_password' => $this->environment->get('MAIL_PASSWORD') ?? '',
            'mail_encryption' => $this->environment->get('MAIL_ENCRYPTION') ?? 'tls',
            'mail_from_address' => $this->environment->get('MAIL_FROM_ADDRESS') ?? '',
            'mail_from_name' => $this->environment->get('MAIL_FROM_NAME') ?? '',
        ];

        return view('installer::smtp', [
            'currentConfig' => $currentConfig,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('SMTP Store - Starting', [
            'all_input' => $request->all(),
        ]);

        try {
            // If user clicks "Skip", just mark step as complete and move on
            if ($request->has('skip')) {
                Log::info('SMTP Store - User skipped SMTP configuration');
                
                $this->installer->completeStep(6);
                $this->installer->setCurrentStep(7);
                
                return redirect()->route('installer.admin');
            }

            // Validate SMTP configuration
            $validated = $request->validate([
                'mail_mailer' => 'required|string|in:smtp,sendmail,mailgun,ses,postmark,log',
                'mail_host' => 'required|string|max:255',
                'mail_port' => 'required|numeric|min:1|max:65535',
                'mail_username' => 'nullable|string|max:255',
                'mail_password' => 'nullable|string|max:255',
                'mail_encryption' => 'required|string|in:tls,ssl,none',
                'mail_from_address' => 'required|email|max:255',
                'mail_from_name' => 'required|string|max:255',
            ]);

            Log::info('SMTP Store - Validation passed', [
                'validated' => $validated,
            ]);

            // Update .env file
            $envData = [
                'MAIL_MAILER' => $validated['mail_mailer'],
                'MAIL_HOST' => $validated['mail_host'],
                'MAIL_PORT' => $validated['mail_port'],
                'MAIL_USERNAME' => $validated['mail_username'] ?? '',
                'MAIL_PASSWORD' => $validated['mail_password'] ?? '',
                'MAIL_ENCRYPTION' => $validated['mail_encryption'] === 'none' ? 'null' : $validated['mail_encryption'],
                'MAIL_FROM_ADDRESS' => $validated['mail_from_address'],
                'MAIL_FROM_NAME' => $validated['mail_from_name'],
            ];

            Log::info('SMTP Store - Writing to .env', [
                'env_data' => $envData,
            ]);

            $this->environment->setMultiple($envData);

            Log::info('SMTP Store - .env written successfully');

            // Mark step as completed
            $this->installer->completeStep(6);
            $this->installer->setCurrentStep(7);

            Log::info('SMTP Store - Completed successfully');

            return redirect()->route('installer.admin');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('SMTP Store - Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('SMTP Store - Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
