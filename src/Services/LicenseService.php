<?php

namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    /**
     * Envato API endpoint for purchase code verification
     */
    private const ENVATO_API_URL = 'https://api.envato.com/v3/market/author/sale';

    public function __construct(
        private InstallerService $installer
    ) {}

    /**
     * Verify a purchase code with the Envato API
     */
    public function verify(string $purchaseCode): LicenseVerificationResult
    {
        try {
            // Development mode - bypass with test code
            if (config('installer.license.dev_mode', false)) {
                if ($purchaseCode === 'dev-test-code-12345678-1234') {
                    // Store dev mode license data
                    $this->storeLicense($purchaseCode, [
                        'item_name' => 'Development Test Item',
                        'buyer' => 'Ouhssini',
                        'purchased_at' => now()->toDateTimeString(),
                        'supported_until' => now()->addYear()->toDateTimeString(),
                        'license_type' => 'regular',
                        'item_id' => 'dev-mode',
                    ], true); // Mark as dev mode

                    return new LicenseVerificationResult(
                        valid: true,
                        itemName: 'Development Test Item',
                        buyerName: 'Ouhssini',
                        purchaseDate: now()->toDateTimeString(),
                        supportedUntil: now()->addYear()->toDateTimeString(),
                        licenseType: 'regular'
                    );
                }
            }

            $personalToken = config('installer.license.envato_personal_token');

            if (empty($personalToken)) {
                return new LicenseVerificationResult(
                    valid: false,
                    error: 'Envato Personal Token not configured. Please set ENVATO_PERSONAL_TOKEN in your .env file.'
                );
            }

            // Validate purchase code format (Envato codes are 36 characters with dashes)
            if (! $this->isValidPurchaseCodeFormat($purchaseCode)) {
                return new LicenseVerificationResult(
                    valid: false,
                    error: 'Invalid purchase code format. Envato purchase codes should be in format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'
                );
            }

            // Make API request to Envato
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$personalToken,
                    'User-Agent' => 'Purchase Code Verification',
                ])
                ->get(self::ENVATO_API_URL, [
                    'code' => $purchaseCode,
                ]);

            // Handle API errors
            if ($response->status() === 404) {
                return new LicenseVerificationResult(
                    valid: false,
                    error: 'Invalid purchase code. This code was not found in Envato records.'
                );
            }

            if ($response->status() === 401) {
                Log::error('Envato API authentication failed', [
                    'status' => $response->status(),
                ]);

                return new LicenseVerificationResult(
                    valid: false,
                    error: 'License verification failed: Invalid Envato Personal Token.'
                );
            }

            if (! $response->successful()) {
                Log::error('Envato API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return new LicenseVerificationResult(
                    valid: false,
                    error: 'License verification failed. Please try again later.'
                );
            }

            $data = $response->json();

            // Validate response structure
            if (! isset($data['item'])) {
                Log::error('Invalid Envato API response structure', [
                    'response' => $data,
                ]);

                return new LicenseVerificationResult(
                    valid: false,
                    error: 'Invalid response from Envato API.'
                );
            }

            // Extract license information
            $itemName = $data['item']['name'] ?? 'Unknown Item';
            $buyerName = $data['buyer'] ?? 'Unknown Buyer';
            $purchaseDate = $data['sold_at'] ?? null;
            $supportedUntil = $data['supported_until'] ?? null;
            $licenseType = $data['license'] ?? 'regular';

            // Store license data
            $this->storeLicense($purchaseCode, [
                'item_name' => $itemName,
                'buyer' => $buyerName,
                'purchased_at' => $purchaseDate,
                'supported_until' => $supportedUntil,
                'license_type' => $licenseType,
                'item_id' => $data['item']['id'] ?? null,
            ]);

            return new LicenseVerificationResult(
                valid: true,
                itemName: $itemName,
                buyerName: $buyerName,
                purchaseDate: $purchaseDate,
                supportedUntil: $supportedUntil,
                licenseType: $licenseType
            );

        } catch (\Exception $e) {
            Log::error('License verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new LicenseVerificationResult(
                valid: false,
                error: 'License verification error: '.$e->getMessage()
            );
        }
    }

    /**
     * Validate purchase code format
     * Envato purchase codes are UUIDs: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
     */
    private function isValidPurchaseCodeFormat(string $code): bool
    {
        return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $code) === 1;
    }

    /**
     * Store license data (hashed reference only)
     */
    public function storeLicense(string $purchaseCode, array $data, bool $devMode = false): void
    {
        // Store hashed license reference
        $hash = hash('sha256', $purchaseCode);
        $this->installer->setSetting('license_hash', $hash);

        // Store license data (without purchase code)
        $licenseData = [
            'item_name' => $data['item_name'] ?? null,
            'buyer' => $data['buyer'] ?? null,
            'purchased_at' => $data['purchased_at'] ?? null,
            'supported_until' => $data['supported_until'] ?? null,
            'license_type' => $data['license_type'] ?? null,
            'item_id' => $data['item_id'] ?? null,
            'verified_at' => now()->toDateTimeString(),
            'dev_mode' => $devMode,
            'enabled' => true,
        ];

        $this->installer->setSetting('license_data', json_encode($licenseData));
    }

    /**
     * Get stored license data
     */
    public function getLicense(): ?array
    {
        $data = $this->installer->getSetting('license_data');

        if (! $data) {
            return null;
        }

        return json_decode($data, true);
    }

    /**
     * Store disabled license status
     */
    public function storeDisabledStatus(): void
    {
        $licenseData = [
            'enabled' => false,
            'skipped_at' => now()->toDateTimeString(),
            'reason' => 'License verification disabled in configuration',
        ];

        $this->installer->setSetting('license_data', json_encode($licenseData));
    }
}
