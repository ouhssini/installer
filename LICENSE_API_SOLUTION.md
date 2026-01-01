# Complete License API Solution
## Secure Envato Purchase Code Verification System

**Version:** 1.0  
**Date:** December 30, 2024  
**Author:** SoftCortex Development Team

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Problem Statement](#problem-statement)
3. [Solution Architecture](#solution-architecture)
4. [Implementation Guide](#implementation-guide)
5. [API Endpoints](#api-endpoints)
6. [Security Features](#security-features)
7. [Advanced Features](#advanced-features)
8. [Deployment Guide](#deployment-guide)
9. [Testing & Monitoring](#testing--monitoring)
10. [Troubleshooting](#troubleshooting)

---

## Executive Summary

This document provides a complete solution for implementing a secure, centralized license verification system for Laravel applications sold on CodeCanyon/Envato. The solution addresses the critical security flaw of exposing Envato Personal Tokens in client applications by implementing a proxy API architecture.

### Key Benefits

- ✅ **Hide Envato Personal Token** - Token stays on your server, never exposed to clients
- ✅ **Centralized Control** - Manage all licenses from one location
- ✅ **Domain Restrictions** - Enforce one license per domain
- ✅ **Remote Revocation** - Disable pirated copies instantly
- ✅ **Installation Tracking** - Monitor all active installations
- ✅ **Analytics Dashboard** - Track usage and installations
- ✅ **Blacklist Management** - Block abusive licenses
- ✅ **Support Validation** - Verify support status before helping customers

---

## Problem Statement

### Current Architecture (Insecure)

```
┌─────────────────┐
│  Client's App   │
│                 │
│  .env file:     │
│  ENVATO_TOKEN=  │
│  abc123...      │ ← TOKEN EXPOSED!
└────────┬────────┘
         │
         │ Direct API Call
         ↓
┌─────────────────┐
│   Envato API    │
└─────────────────┘
```

### Problems

1. **Token Exposure**: Envato Personal Token is visible in client's `.env` file
2. **No Control**: Can't revoke licenses or track installations
3. **Easy Bypass**: Users can disable license checks in config files
4. **No Analytics**: Can't see who's using your product
5. **Token Abuse**: Users can use your token for their own API calls
6. **No Domain Limits**: One license can be used on unlimited domains

---

## Solution Architecture

### New Architecture (Secure)

```
┌─────────────────┐
│  Client's App   │
│                 │
│  Only sends:    │
│  - Purchase Code│
│  - Product ID   │
│  - Domain       │
└────────┬────────┘
         │
         │ HTTPS POST
         ↓
┌─────────────────┐
│   YOUR API      │
│  (Your Server)  │
│                 │
│  - Validates    │
│  - Tracks       │
│  - Controls     │
│  - Stores       │
│                 │
│  ENVATO_TOKEN=  │
│  abc123...      │ ← TOKEN HIDDEN!
└────────┬────────┘
         │
         │ Envato API Call
         ↓
┌─────────────────┐
│   Envato API    │
└─────────────────┘
```

### Data Flow

```
Installation Flow:
1. User enters purchase code
2. Client app → YOUR API (purchase_code, product_id, domain)
3. YOUR API validates format
4. YOUR API checks database (already used?)
5. YOUR API → Envato API (with YOUR token)
6. Envato returns license data
7. YOUR API stores in database
8. YOUR API → Client app (valid/invalid)
9. Client stores license hash locally

Periodic Validation:
1. Every 7 days, client → YOUR API (license_hash, domain)
2. YOUR API checks database
3. YOUR API → Client (valid/invalid)
4. If invalid, client enters grace period (30 days)
5. After grace period, app stops working
```

---

## Implementation Guide

### Part 1: Create Your License API Server

#### Step 1.1: Create New Laravel Project

```bash
# Create new Laravel project for your License API
composer create-project laravel/laravel license-api
cd license-api

# Install dependencies
composer require laravel/sanctum
```

#### Step 1.2: Configure Environment

```env
# .env
APP_NAME="License API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://license-api.yourcompany.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=license_api
DB_USERNAME=root
DB_PASSWORD=your_password

# Your Envato Personal Token (KEEP SECRET!)
ENVATO_PERSONAL_TOKEN=your-envato-token-here

# API Rate Limiting
API_RATE_LIMIT=60
```

#### Step 1.3: Create Database Migration

```bash
php artisan make:migration create_licenses_table
```

```php
<?php
// database/migrations/xxxx_create_licenses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            
            // License identification
            $table->string('purchase_code_hash', 64)->unique();
            $table->string('product_id', 50);
            $table->string('domain', 255);
            
            // Envato data
            $table->string('item_id', 50)->nullable();
            $table->string('item_name')->nullable();
            $table->string('buyer')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('supported_until')->nullable();
            $table->string('license_type', 20)->default('regular');
            
            // Status tracking
            $table->boolean('active')->default(true);
            $table->boolean('blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            
            // Usage tracking
            $table->timestamp('first_verified_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('verification_count')->default(0);
            $table->integer('check_count')->default(0);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->ipAddress('last_ip')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['purchase_code_hash', 'product_id']);
            $table->index(['domain', 'active']);
            $table->index(['product_id', 'active']);
            $table->index('blacklisted');
        });
        
        // Activity log table
        Schema::create('license_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->onDelete('cascade');
            $table->string('action', 50); // verify, validate, revoke, etc.
            $table->string('domain')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
            
            $table->index(['license_id', 'action']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_activities');
        Schema::dropIfExists('licenses');
    }
};
```

```bash
php artisan migrate
```

#### Step 1.4: Create License Model

```bash
php artisan make:model License
```

```php
<?php
// app/Models/License.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_code_hash',
        'product_id',
        'domain',
        'item_id',
        'item_name',
        'buyer',
        'purchased_at',
        'supported_until',
        'license_type',
        'active',
        'blacklisted',
        'blacklist_reason',
        'first_verified_at',
        'last_verified_at',
        'last_checked_at',
        'verification_count',
        'check_count',
        'metadata',
        'last_ip',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'supported_until' => 'datetime',
        'first_verified_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'active' => 'boolean',
        'blacklisted' => 'boolean',
        'metadata' => 'array',
    ];

    public function activities(): HasMany
    {
        return $this->hasMany(LicenseActivity::class);
    }

    public function logActivity(string $action, array $data = []): void
    {
        $this->activities()->create([
            'action' => $action,
            'domain' => request()->input('domain'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->supported_until && $this->supported_until->isPast();
    }

    public function isValid(): bool
    {
        return $this->active && !$this->blacklisted;
    }
}
```

```bash
php artisan make:model LicenseActivity
```

```php
<?php
// app/Models/LicenseActivity.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseActivity extends Model
{
    protected $fillable = [
        'license_id',
        'action',
        'domain',
        'ip_address',
        'user_agent',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
```

#### Step 1.5: Create API Controller

```bash
php artisan make:controller Api/LicenseController
```


```php
<?php
// app/Http/Controllers/Api/LicenseController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    private const ENVATO_API_URL = 'https://api.envato.com/v3/market/author/sale';

    /**
     * Verify purchase code (first-time installation)
     * 
     * POST /api/verify
     * Body: {
     *   "purchase_code": "xxxx-xxxx-xxxx-xxxx",
     *   "product_id": "your-product-12345",
     *   "domain": "customer-site.com"
     * }
     */
    public function verify(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'purchase_code' => 'required|string|size:36',
            'product_id' => 'required|string|max:50',
            'domain' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $purchaseCode = $request->purchase_code;
        $productId = $request->product_id;
        $domain = $this->normalizeDomain($request->domain);

        // 1. Validate purchase code format
        if (!$this->isValidPurchaseCodeFormat($purchaseCode)) {
            return response()->json([
                'valid' => false,
                'error' => 'Invalid purchase code format. Expected: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            ], 422);
        }

        $purchaseCodeHash = hash('sha256', $purchaseCode);

        // 2. Check if license is blacklisted
        $blacklisted = License::where('purchase_code_hash', $purchaseCodeHash)
            ->where('blacklisted', true)
            ->first();

        if ($blacklisted) {
            Log::warning('Blacklisted license attempt', [
                'domain' => $domain,
                'reason' => $blacklisted->blacklist_reason,
            ]);

            return response()->json([
                'valid' => false,
                'error' => 'This license has been revoked. Reason: ' . ($blacklisted->blacklist_reason ?? 'Violation of terms'),
            ], 403);
        }

        // 3. Check if already used on different domain
        $existingLicense = License::where('purchase_code_hash', $purchaseCodeHash)
            ->where('product_id', $productId)
            ->where('domain', '!=', $domain)
            ->where('active', true)
            ->first();

        if ($existingLicense) {
            return response()->json([
                'valid' => false,
                'error' => 'This purchase code is already activated on another domain: ' . $existingLicense->domain,
            ], 422);
        }

        // 4. Verify with Envato API
        $envatoResult = $this->verifyWithEnvato($purchaseCode);

        if (!$envatoResult['valid']) {
            return response()->json([
                'valid' => false,
                'error' => $envatoResult['error'],
            ], 422);
        }


        // 5. Verify product ID matches (optional but recommended)
        if (isset($envatoResult['item_id']) && $envatoResult['item_id'] != $productId) {
            return response()->json([
                'valid' => false,
                'error' => 'This purchase code is valid but for a different product.',
            ], 422);
        }

        // 6. Store or update license
        $license = License::updateOrCreate(
            [
                'purchase_code_hash' => $purchaseCodeHash,
                'product_id' => $productId,
            ],
            [
                'domain' => $domain,
                'item_id' => $envatoResult['item_id'] ?? null,
                'item_name' => $envatoResult['item_name'],
                'buyer' => $envatoResult['buyer'],
                'purchased_at' => $envatoResult['purchased_at'],
                'supported_until' => $envatoResult['supported_until'],
                'license_type' => $envatoResult['license_type'],
                'first_verified_at' => $license->first_verified_at ?? now(),
                'last_verified_at' => now(),
                'verification_count' => ($license->verification_count ?? 0) + 1,
                'active' => true,
                'last_ip' => $request->ip(),
            ]
        );

        // Log activity
        $license->logActivity('verify', [
            'success' => true,
            'item_name' => $envatoResult['item_name'],
        ]);

        // 7. Return success
        return response()->json([
            'valid' => true,
            'license' => [
                'item_name' => $envatoResult['item_name'],
                'buyer' => $envatoResult['buyer'],
                'license_type' => $envatoResult['license_type'],
                'purchased_at' => $envatoResult['purchased_at'],
                'supported_until' => $envatoResult['supported_until'],
            ],
        ]);
    }

    /**
     * Validate existing license (periodic checks)
     * 
     * POST /api/validate
     * Body: {
     *   "license_hash": "sha256...",
     *   "product_id": "your-product-12345",
     *   "domain": "customer-site.com"
     * }
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_hash' => 'required|string|size:64',
            'product_id' => 'required|string|max:50',
            'domain' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'error' => $validator->errors()->first(),
            ], 422);
        }

        $domain = $this->normalizeDomain($request->domain);

        $license = License::where('purchase_code_hash', $request->license_hash)
            ->where('product_id', $request->product_id)
            ->where('domain', $domain)
            ->first();

        if (!$license) {
            return response()->json([
                'valid' => false,
                'error' => 'License not found for this domain',
            ], 404);
        }

        if ($license->blacklisted) {
            $license->logActivity('validate_blocked', [
                'reason' => 'blacklisted',
            ]);

            return response()->json([
                'valid' => false,
                'error' => 'License has been revoked',
            ], 403);
        }

        if (!$license->active) {
            $license->logActivity('validate_blocked', [
                'reason' => 'inactive',
            ]);

            return response()->json([
                'valid' => false,
                'error' => 'License is inactive',
            ], 403);
        }

        // Update check timestamp
        $license->update([
            'last_checked_at' => now(),
            'check_count' => $license->check_count + 1,
            'last_ip' => $request->ip(),
        ]);

        $license->logActivity('validate', [
            'success' => true,
        ]);

        return response()->json([
            'valid' => true,
            'supported_until' => $license->supported_until,
            'license_type' => $license->license_type,
        ]);
    }


    /**
     * Verify purchase code with Envato API
     */
    private function verifyWithEnvato(string $purchaseCode): array
    {
        $cacheKey = 'envato_verify_' . hash('sha256', $purchaseCode);

        // Check cache first (reduce API calls)
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $personalToken = config('services.envato.personal_token');

            if (empty($personalToken)) {
                Log::error('Envato Personal Token not configured');
                return [
                    'valid' => false,
                    'error' => 'License verification service not configured',
                ];
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $personalToken,
                    'User-Agent' => 'License Verification API/1.0',
                ])
                ->get(self::ENVATO_API_URL, [
                    'code' => $purchaseCode,
                ]);

            if ($response->status() === 404) {
                return [
                    'valid' => false,
                    'error' => 'Purchase code not found in Envato records',
                ];
            }

            if ($response->status() === 401) {
                Log::error('Envato API authentication failed');
                return [
                    'valid' => false,
                    'error' => 'License verification service authentication failed',
                ];
            }

            if (!$response->successful()) {
                Log::error('Envato API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [
                    'valid' => false,
                    'error' => 'License verification service temporarily unavailable',
                ];
            }

            $data = $response->json();

            if (!isset($data['item'])) {
                Log::error('Invalid Envato API response', ['response' => $data]);
                return [
                    'valid' => false,
                    'error' => 'Invalid response from license verification service',
                ];
            }

            $result = [
                'valid' => true,
                'item_id' => $data['item']['id'] ?? null,
                'item_name' => $data['item']['name'] ?? 'Unknown Item',
                'buyer' => $data['buyer'] ?? 'Unknown Buyer',
                'purchased_at' => $data['sold_at'] ?? null,
                'supported_until' => $data['supported_until'] ?? null,
                'license_type' => $data['license'] ?? 'regular',
            ];

            // Cache for 1 hour
            Cache::put($cacheKey, $result, 3600);

            return $result;

        } catch (\Exception $e) {
            Log::error('Envato verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'valid' => false,
                'error' => 'License verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validate purchase code format (UUID)
     */
    private function isValidPurchaseCodeFormat(string $code): bool
    {
        return preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
            $code
        ) === 1;
    }

    /**
     * Normalize domain (remove www, http, https, trailing slash)
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower($domain);
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = rtrim($domain, '/');
        return $domain;
    }
}
```

#### Step 1.6: Configure Routes

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/verify', [LicenseController::class, 'verify'])
        ->middleware('throttle:10,1'); // 10 requests per minute
    
    Route::post('/validate', [LicenseController::class, 'validate'])
        ->middleware('throttle:60,1'); // 60 requests per minute
});
```


#### Step 1.7: Configure Services

```php
<?php
// config/services.php

return [
    // ... other services

    'envato' => [
        'personal_token' => env('ENVATO_PERSONAL_TOKEN'),
    ],
];
```

---

### Part 2: Update Your Installer Package

#### Step 2.1: Update LicenseService

```php
<?php
// src/Services/LicenseService.php

namespace SoftCortex\Installer\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    // YOUR License API endpoint
    private const LICENSE_API_URL = 'https://license-api.yourcompany.com/api/v1';
    
    // YOUR unique product ID
    private const PRODUCT_ID = 'magic-installer-12345';

    public function __construct(
        private InstallerService $installer
    ) {}

    /**
     * Verify purchase code via YOUR API
     */
    public function verify(string $purchaseCode): LicenseVerificationResult
    {
        try {
            $domain = $this->getCurrentDomain();

            // Validate format locally first
            if (!$this->isValidPurchaseCodeFormat($purchaseCode)) {
                return new LicenseVerificationResult(
                    valid: false,
                    error: 'Invalid purchase code format. Expected: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'
                );
            }

            // Call YOUR API (not Envato directly)
            $response = Http::timeout(20)
                ->post(self::LICENSE_API_URL . '/verify', [
                    'purchase_code' => $purchaseCode,
                    'product_id' => self::PRODUCT_ID,
                    'domain' => $domain,
                ]);

            // Handle errors
            if (!$response->successful()) {
                $error = $response->json('error') ?? 'License verification failed';
                
                Log::warning('License verification failed', [
                    'status' => $response->status(),
                    'error' => $error,
                    'domain' => $domain,
                ]);

                return new LicenseVerificationResult(
                    valid: false,
                    error: $error
                );
            }

            $data = $response->json();

            if (!$data['valid']) {
                return new LicenseVerificationResult(
                    valid: false,
                    error: $data['error'] ?? 'Invalid license'
                );
            }

            $license = $data['license'];

            // Store license data locally
            $this->storeLicense($purchaseCode, $license);

            return new LicenseVerificationResult(
                valid: true,
                itemName: $license['item_name'],
                buyerName: $license['buyer'],
                purchaseDate: $license['purchased_at'] ?? null,
                supportedUntil: $license['supported_until'] ?? null,
                licenseType: $license['license_type']
            );

        } catch (\Exception $e) {
            Log::error('License verification exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new LicenseVerificationResult(
                valid: false,
                error: 'License verification error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Validate existing license (periodic check)
     */
    public function validateLicense(): bool
    {
        try {
            $domain = $this->getCurrentDomain();
            $licenseHash = $this->installer->getSetting('license_hash');

            if (!$licenseHash) {
                return false;
            }

            $response = Http::timeout(10)
                ->post(self::LICENSE_API_URL . '/validate', [
                    'license_hash' => $licenseHash,
                    'product_id' => self::PRODUCT_ID,
                    'domain' => $domain,
                ]);

            if (!$response->successful()) {
                Log::warning('License validation failed', [
                    'status' => $response->status(),
                    'domain' => $domain,
                ]);
                return false;
            }

            $data = $response->json();
            return $data['valid'] === true;

        } catch (\Exception $e) {
            // Network error - allow grace period
            Log::warning('License validation exception', [
                'message' => $e->getMessage(),
            ]);
            return true; // Allow grace period on network errors
        }
    }

    /**
     * Check if license validation is needed
     */
    public function needsValidation(): bool
    {
        $lastCheck = $this->installer->getSetting('last_license_check');
        
        if (!$lastCheck) {
            return true;
        }

        // Check every 7 days
        return now()->diffInDays($lastCheck) >= 7;
    }

    /**
     * Validate purchase code format
     */
    private function isValidPurchaseCodeFormat(string $code): bool
    {
        return preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
            $code
        ) === 1;
    }

    /**
     * Store license data locally
     */
    private function storeLicense(string $purchaseCode, array $data): void
    {
        $hash = hash('sha256', $purchaseCode);
        
        $this->installer->setSetting('license_hash', $hash);
        $this->installer->setSetting('license_data', json_encode($data));
        $this->installer->setSetting('last_license_check', now()->toDateTimeString());
        $this->installer->setSetting('grace_period_started', null);
    }

    /**
     * Get current domain
     */
    private function getCurrentDomain(): string
    {
        $domain = request()->getHost();
        $domain = strtolower($domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        return $domain;
    }

    /**
     * Get stored license data
     */
    public function getLicense(): ?array
    {
        $data = $this->installer->getSetting('license_data');
        return $data ? json_decode($data, true) : null;
    }
}
```


#### Step 2.2: Add Periodic Validation Middleware

```bash
php artisan make:middleware ValidateLicense
```

```php
<?php
// src/Http/Middleware/ValidateLicense.php

namespace SoftCortex\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SoftCortex\Installer\Services\LicenseService;
use SoftCortex\Installer\Services\InstallerService;
use Symfony\Component\HttpFoundation\Response;

class ValidateLicense
{
    public function __construct(
        private LicenseService $license,
        private InstallerService $installer
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Skip in local development
        if (app()->environment('local') && config('app.debug')) {
            return $next($request);
        }

        // Skip installer routes
        if ($request->is('install*')) {
            return $next($request);
        }

        // Check if needs validation
        if ($this->license->needsValidation()) {
            $valid = $this->license->validateLicense();

            if ($valid) {
                // Update last check time
                $this->installer->setSetting('last_license_check', now()->toDateTimeString());
                $this->installer->setSetting('grace_period_started', null);
            } else {
                // Start or check grace period
                $gracePeriodStarted = $this->installer->getSetting('grace_period_started');

                if (!$gracePeriodStarted) {
                    // Start grace period (30 days)
                    $this->installer->setSetting('grace_period_started', now()->toDateTimeString());
                } else {
                    // Check if grace period expired
                    $gracePeriodStart = \Carbon\Carbon::parse($gracePeriodStarted);
                    
                    if ($gracePeriodStart->addDays(30)->isPast()) {
                        // Grace period expired - block access
                        abort(403, 'License verification failed. Please contact support.');
                    }
                }
            }
        }

        return $next($request);
    }
}
```

#### Step 2.3: Register Middleware

```php
<?php
// src/InstallerServiceProvider.php

namespace SoftCortex\Installer;

use Illuminate\Support\ServiceProvider;
use SoftCortex\Installer\Http\Middleware\ValidateLicense;

class InstallerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ... existing code

        // Register license validation middleware
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', ValidateLicense::class);
    }
}
```

---

## API Endpoints

### 1. Verify Purchase Code

**Endpoint:** `POST /api/v1/verify`

**Purpose:** First-time license verification during installation

**Request:**
```json
{
  "purchase_code": "12345678-1234-1234-1234-123456789012",
  "product_id": "magic-installer-12345",
  "domain": "customer-site.com"
}
```

**Success Response (200):**
```json
{
  "valid": true,
  "license": {
    "item_name": "Magic Installer",
    "buyer": "John Doe",
    "license_type": "regular",
    "purchased_at": "2024-01-15T10:30:00Z",
    "supported_until": "2025-01-15T10:30:00Z"
  }
}
```

**Error Response (422):**
```json
{
  "valid": false,
  "error": "This purchase code is already activated on another domain: example.com"
}
```

**Error Response (403):**
```json
{
  "valid": false,
  "error": "This license has been revoked. Reason: Violation of terms"
}
```

---

### 2. Validate License

**Endpoint:** `POST /api/v1/validate`

**Purpose:** Periodic license validation (every 7 days)

**Request:**
```json
{
  "license_hash": "abc123def456...",
  "product_id": "magic-installer-12345",
  "domain": "customer-site.com"
}
```

**Success Response (200):**
```json
{
  "valid": true,
  "supported_until": "2025-01-15T10:30:00Z",
  "license_type": "regular"
}
```

**Error Response (404):**
```json
{
  "valid": false,
  "error": "License not found for this domain"
}
```

**Error Response (403):**
```json
{
  "valid": false,
  "error": "License has been revoked"
}
```

---

## Security Features

### 1. Token Protection
- ✅ Envato Personal Token stored only on YOUR server
- ✅ Never exposed to clients
- ✅ Can't be stolen or abused

### 2. Domain Restrictions
- ✅ One license = One domain
- ✅ Prevents license sharing
- ✅ Tracks all installations

### 3. Blacklist System
- ✅ Instantly revoke pirated licenses
- ✅ Block abusive users
- ✅ Add revocation reasons

### 4. Rate Limiting
- ✅ Verify: 10 requests/minute
- ✅ Validate: 60 requests/minute
- ✅ Prevents API abuse

### 5. Activity Logging
- ✅ Track all verification attempts
- ✅ Monitor suspicious activity
- ✅ Audit trail for support

### 6. Grace Period
- ✅ 30-day grace period if validation fails
- ✅ Handles network issues gracefully
- ✅ User-friendly approach

### 7. Caching
- ✅ Cache Envato API responses (1 hour)
- ✅ Reduces API calls
- ✅ Faster verification


---

## Advanced Features

### Feature 1: Admin Dashboard

Create an admin panel to manage licenses:

```bash
php artisan make:controller Admin/LicenseManagementController
```

```php
<?php
// app/Http/Controllers/Admin/LicenseManagementController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;

class LicenseManagementController extends Controller
{
    /**
     * List all licenses
     */
    public function index(Request $request)
    {
        $query = License::with('activities')
            ->orderBy('created_at', 'desc');

        // Filter by product
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by status
        if ($request->status === 'active') {
            $query->where('active', true)->where('blacklisted', false);
        } elseif ($request->status === 'blacklisted') {
            $query->where('blacklisted', true);
        } elseif ($request->status === 'inactive') {
            $query->where('active', false);
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('domain', 'like', '%' . $request->search . '%')
                  ->orWhere('buyer', 'like', '%' . $request->search . '%')
                  ->orWhere('item_name', 'like', '%' . $request->search . '%');
            });
        }

        $licenses = $query->paginate(50);

        return view('admin.licenses.index', compact('licenses'));
    }

    /**
     * Show license details
     */
    public function show(License $license)
    {
        $license->load('activities');
        return view('admin.licenses.show', compact('license'));
    }

    /**
     * Blacklist a license
     */
    public function blacklist(Request $request, License $license)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $license->update([
            'blacklisted' => true,
            'blacklist_reason' => $request->reason,
        ]);

        $license->logActivity('blacklisted', [
            'reason' => $request->reason,
            'admin' => auth()->user()->name ?? 'System',
        ]);

        return redirect()->back()->with('success', 'License blacklisted successfully');
    }

    /**
     * Remove from blacklist
     */
    public function unblacklist(License $license)
    {
        $license->update([
            'blacklisted' => false,
            'blacklist_reason' => null,
        ]);

        $license->logActivity('unblacklisted', [
            'admin' => auth()->user()->name ?? 'System',
        ]);

        return redirect()->back()->with('success', 'License removed from blacklist');
    }

    /**
     * Deactivate license
     */
    public function deactivate(License $license)
    {
        $license->update(['active' => false]);
        
        $license->logActivity('deactivated', [
            'admin' => auth()->user()->name ?? 'System',
        ]);

        return redirect()->back()->with('success', 'License deactivated');
    }

    /**
     * Activate license
     */
    public function activate(License $license)
    {
        $license->update(['active' => true]);
        
        $license->logActivity('activated', [
            'admin' => auth()->user()->name ?? 'System',
        ]);

        return redirect()->back()->with('success', 'License activated');
    }

    /**
     * Analytics dashboard
     */
    public function analytics()
    {
        $stats = [
            'total_licenses' => License::count(),
            'active_licenses' => License::where('active', true)->where('blacklisted', false)->count(),
            'blacklisted' => License::where('blacklisted', true)->count(),
            'expired_support' => License::where('supported_until', '<', now())->count(),
            'this_month' => License::whereMonth('created_at', now()->month)->count(),
            'this_week' => License::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        // Top products
        $topProducts = License::selectRaw('product_id, item_name, COUNT(*) as count')
            ->groupBy('product_id', 'item_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // Recent activations
        $recentActivations = License::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.licenses.analytics', compact('stats', 'topProducts', 'recentActivations'));
    }
}
```

---

### Feature 2: Webhook Notifications

Get notified when licenses are verified:

```php
<?php
// app/Http/Controllers/Api/LicenseController.php

// Add to verify() method after successful verification:

// Send webhook notification
if (config('services.webhook.url')) {
    Http::post(config('services.webhook.url'), [
        'event' => 'license.verified',
        'license' => [
            'domain' => $domain,
            'product_id' => $productId,
            'buyer' => $envatoResult['buyer'],
            'item_name' => $envatoResult['item_name'],
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
}
```

---

### Feature 3: Multi-Domain Support

Allow one license on multiple domains (e.g., staging + production):

```php
<?php
// In LicenseController::verify()

// Replace single domain check with:
$maxDomains = 2; // Allow 2 domains per license

$existingDomains = License::where('purchase_code_hash', $purchaseCodeHash)
    ->where('product_id', $productId)
    ->where('active', true)
    ->count();

if ($existingDomains >= $maxDomains) {
    $domains = License::where('purchase_code_hash', $purchaseCodeHash)
        ->pluck('domain')
        ->implode(', ');
    
    return response()->json([
        'valid' => false,
        'error' => "Maximum domains reached ($maxDomains). Already used on: $domains",
    ], 422);
}
```

---

### Feature 4: License Transfer

Allow users to transfer license to new domain:

```php
<?php
// app/Http/Controllers/Api/LicenseController.php

public function transfer(Request $request)
{
    $validator = Validator::make($request->all(), [
        'purchase_code' => 'required|string|size:36',
        'old_domain' => 'required|string|max:255',
        'new_domain' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'error' => $validator->errors()->first(),
        ], 422);
    }

    $purchaseCodeHash = hash('sha256', $request->purchase_code);
    $oldDomain = $this->normalizeDomain($request->old_domain);
    $newDomain = $this->normalizeDomain($request->new_domain);

    $license = License::where('purchase_code_hash', $purchaseCodeHash)
        ->where('domain', $oldDomain)
        ->first();

    if (!$license) {
        return response()->json([
            'success' => false,
            'error' => 'License not found for old domain',
        ], 404);
    }

    // Check if new domain already has a license
    $existingOnNew = License::where('purchase_code_hash', $purchaseCodeHash)
        ->where('domain', $newDomain)
        ->exists();

    if ($existingOnNew) {
        return response()->json([
            'success' => false,
            'error' => 'License already exists on new domain',
        ], 422);
    }

    // Transfer
    $license->update(['domain' => $newDomain]);
    
    $license->logActivity('transferred', [
        'old_domain' => $oldDomain,
        'new_domain' => $newDomain,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'License transferred successfully',
    ]);
}
```


---

## Deployment Guide

### Step 1: Prepare Your Server

**Requirements:**
- PHP 8.2+
- MySQL/PostgreSQL
- SSL Certificate (HTTPS required)
- Composer
- Git

**Recommended Hosting:**
- DigitalOcean ($6/month)
- AWS EC2 (t3.micro)
- Linode ($5/month)
- Vultr ($6/month)

### Step 2: Deploy License API

```bash
# SSH into your server
ssh user@license-api.yourcompany.com

# Clone your repository
git clone https://github.com/yourcompany/license-api.git
cd license-api

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Configure environment
cp .env.example .env
nano .env

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Setup supervisor for queue workers (optional)
sudo nano /etc/supervisor/conf.d/license-api.conf
```

**Supervisor Configuration:**
```ini
[program:license-api-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/license-api/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/license-api/storage/logs/worker.log
```

### Step 3: Configure Nginx

```nginx
# /etc/nginx/sites-available/license-api.yourcompany.com

server {
    listen 80;
    listen [::]:80;
    server_name license-api.yourcompany.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name license-api.yourcompany.com;
    root /var/www/license-api/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/license-api.yourcompany.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/license-api.yourcompany.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/license-api.yourcompany.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Setup SSL with Let's Encrypt
sudo certbot --nginx -d license-api.yourcompany.com
```

### Step 4: Setup Monitoring

**Install Laravel Telescope (Development):**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Install Laravel Horizon (Production Queue):**
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

**Setup Log Monitoring:**
```bash
# Install Papertrail or similar
# Add to .env:
LOG_CHANNEL=stack
LOG_PAPERTRAIL_URL=logs.papertrailapp.com
LOG_PAPERTRAIL_PORT=12345
```

### Step 5: Update Your Installer Package

Update the API URL in your installer:

```php
// src/Services/LicenseService.php
private const LICENSE_API_URL = 'https://license-api.yourcompany.com/api/v1';
private const PRODUCT_ID = 'magic-installer-12345'; // Your unique ID
```

Publish new version to Packagist:
```bash
git tag v2.0.0
git push origin v2.0.0
```

---

## Testing & Monitoring

### Unit Tests

```php
<?php
// tests/Feature/LicenseVerificationTest.php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LicenseVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_purchase_code_verification()
    {
        $response = $this->postJson('/api/v1/verify', [
            'purchase_code' => '12345678-1234-1234-1234-123456789012',
            'product_id' => 'test-product',
            'domain' => 'test-site.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
            ]);

        $this->assertDatabaseHas('licenses', [
            'domain' => 'test-site.com',
            'product_id' => 'test-product',
        ]);
    }

    public function test_duplicate_domain_rejection()
    {
        License::create([
            'purchase_code_hash' => hash('sha256', '12345678-1234-1234-1234-123456789012'),
            'product_id' => 'test-product',
            'domain' => 'existing-site.com',
            'active' => true,
        ]);

        $response = $this->postJson('/api/v1/verify', [
            'purchase_code' => '12345678-1234-1234-1234-123456789012',
            'product_id' => 'test-product',
            'domain' => 'new-site.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'valid' => false,
            ]);
    }

    public function test_blacklisted_license_rejection()
    {
        License::create([
            'purchase_code_hash' => hash('sha256', '12345678-1234-1234-1234-123456789012'),
            'product_id' => 'test-product',
            'domain' => 'test-site.com',
            'blacklisted' => true,
            'blacklist_reason' => 'Piracy',
        ]);

        $response = $this->postJson('/api/v1/verify', [
            'purchase_code' => '12345678-1234-1234-1234-123456789012',
            'product_id' => 'test-product',
            'domain' => 'test-site.com',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'valid' => false,
            ]);
    }
}
```

Run tests:
```bash
php artisan test
```

### Monitoring Checklist

- [ ] Setup uptime monitoring (UptimeRobot, Pingdom)
- [ ] Configure error tracking (Sentry, Bugsnag)
- [ ] Enable log aggregation (Papertrail, Loggly)
- [ ] Setup performance monitoring (New Relic, Scout)
- [ ] Configure database backups (daily)
- [ ] Setup SSL certificate renewal alerts
- [ ] Monitor API response times
- [ ] Track API usage and rate limits


---

## Troubleshooting

### Common Issues

#### Issue 1: "License verification service not configured"

**Cause:** Envato Personal Token not set

**Solution:**
```bash
# On your License API server
nano .env

# Add:
ENVATO_PERSONAL_TOKEN=your-token-here

# Clear cache
php artisan config:clear
php artisan config:cache
```

---

#### Issue 2: "Connection timeout"

**Cause:** Firewall blocking requests or slow network

**Solution:**
```php
// Increase timeout in client app
$response = Http::timeout(30) // Increase from 20 to 30
    ->post(self::LICENSE_API_URL . '/verify', [...]);
```

---

#### Issue 3: "License not found for this domain"

**Cause:** Domain mismatch (www vs non-www)

**Solution:**
Both API and client normalize domains. Check:
```php
// Debug in client app
$domain = request()->getHost();
echo "Current domain: " . $domain; // Should be without www
```

---

#### Issue 4: "Too Many Requests (429)"

**Cause:** Rate limit exceeded

**Solution:**
```php
// Adjust rate limits in routes/api.php
Route::post('/verify', [LicenseController::class, 'verify'])
    ->middleware('throttle:20,1'); // Increase from 10 to 20
```

---

#### Issue 5: SSL Certificate Errors

**Cause:** Invalid or expired SSL certificate

**Solution:**
```bash
# Renew Let's Encrypt certificate
sudo certbot renew
sudo systemctl reload nginx

# Or disable SSL verification (NOT RECOMMENDED for production)
$response = Http::withoutVerifying()->post(...);
```

---

#### Issue 6: Database Connection Errors

**Cause:** Database credentials incorrect or server down

**Solution:**
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check credentials in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=license_api
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

### Debug Mode

Enable debug logging in your License API:

```php
// app/Http/Controllers/Api/LicenseController.php

// Add at the start of verify() method:
Log::info('License verification request', [
    'purchase_code' => substr($purchaseCode, 0, 8) . '...',
    'product_id' => $productId,
    'domain' => $domain,
    'ip' => $request->ip(),
]);
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

---

### Performance Optimization

#### 1. Enable OPcache

```ini
# /etc/php/8.2/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

#### 2. Use Redis for Caching

```bash
composer require predis/predis
```

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### 3. Database Indexing

```php
// Ensure indexes exist
Schema::table('licenses', function (Blueprint $table) {
    $table->index(['purchase_code_hash', 'product_id']);
    $table->index(['domain', 'active']);
});
```

#### 4. Query Optimization

```php
// Use select() to limit columns
$license = License::select(['id', 'domain', 'active', 'blacklisted'])
    ->where('purchase_code_hash', $hash)
    ->first();
```

---

## Security Best Practices

### 1. Environment Variables

**Never commit sensitive data:**
```bash
# .gitignore
.env
.env.backup
.env.production
```

### 2. API Authentication (Optional)

Add API key authentication for extra security:

```php
// app/Http/Middleware/ValidateApiKey.php
public function handle($request, Closure $next)
{
    $apiKey = $request->header('X-API-Key');
    
    if ($apiKey !== config('app.api_key')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    return $next($request);
}
```

### 3. IP Whitelisting

Restrict API access to specific IPs:

```php
// In controller
$allowedIps = ['1.2.3.4', '5.6.7.8'];

if (!in_array($request->ip(), $allowedIps)) {
    return response()->json(['error' => 'Access denied'], 403);
}
```

### 4. Request Signing

Sign requests to prevent tampering:

```php
// Client side
$signature = hash_hmac('sha256', json_encode($data), 'secret-key');
$headers = ['X-Signature' => $signature];

// Server side
$expectedSignature = hash_hmac('sha256', $request->getContent(), 'secret-key');
if (!hash_equals($expectedSignature, $request->header('X-Signature'))) {
    abort(401, 'Invalid signature');
}
```

### 5. CORS Configuration

```php
// config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['POST'],
    'allowed_origins' => ['*'], // Or specific domains
    'allowed_headers' => ['*'],
    'max_age' => 0,
];
```

---

## Cost Analysis

### Monthly Costs

| Service | Cost | Purpose |
|---------|------|---------|
| DigitalOcean Droplet | $6 | API Server |
| Domain Name | $1 | license-api.yourcompany.com |
| SSL Certificate | $0 | Let's Encrypt (free) |
| Database Backups | $1 | DigitalOcean backups |
| Monitoring (UptimeRobot) | $0 | Free tier |
| **Total** | **$8/month** | |

### ROI Calculation

**Scenario:** You sell 100 licenses/month at $50 each

- **Revenue:** $5,000/month
- **API Cost:** $8/month
- **ROI:** 62,400%

**The API pays for itself with just 1 sale!**

---

## Comparison: Before vs After

### Before (Direct Envato API)

| Feature | Status |
|---------|--------|
| Token Security | ❌ Exposed in .env |
| Domain Control | ❌ No |
| Revoke Licenses | ❌ No |
| Track Installations | ❌ No |
| Analytics | ❌ No |
| Blacklist | ❌ No |
| Multi-Domain | ❌ No |
| License Transfer | ❌ No |
| **Monthly Cost** | **$0** |
| **Control Level** | **0%** |

### After (Your License API)

| Feature | Status |
|---------|--------|
| Token Security | ✅ Hidden on your server |
| Domain Control | ✅ Yes |
| Revoke Licenses | ✅ Yes |
| Track Installations | ✅ Yes |
| Analytics | ✅ Yes |
| Blacklist | ✅ Yes |
| Multi-Domain | ✅ Yes |
| License Transfer | ✅ Yes |
| **Monthly Cost** | **$8** |
| **Control Level** | **100%** |

---

## Conclusion

This License API solution provides:

1. **Complete Control** - You control all license validations
2. **Enhanced Security** - Envato token never exposed
3. **Better Analytics** - Track all installations and usage
4. **Piracy Prevention** - Revoke licenses, enforce domain limits
5. **Professional Image** - Shows you're serious about your product
6. **Scalability** - Handles thousands of installations
7. **Low Cost** - Only $8/month for complete control

### Next Steps

1. ✅ Deploy License API to your server
2. ✅ Update installer package to use new API
3. ✅ Test thoroughly with real purchase codes
4. ✅ Setup monitoring and alerts
5. ✅ Create admin dashboard for license management
6. ✅ Document API for your team
7. ✅ Release new version to customers

---

## Support & Resources

### Documentation
- Laravel: https://laravel.com/docs
- Envato API: https://build.envato.com/api/
- Let's Encrypt: https://letsencrypt.org/

### Tools
- Postman: Test API endpoints
- Laravel Telescope: Debug requests
- Laravel Horizon: Monitor queues
- Sentry: Error tracking

### Community
- Laravel Discord: https://discord.gg/laravel
- Envato Forums: https://forums.envato.com/
- Stack Overflow: Tag with `laravel` and `envato`

---

**Document Version:** 1.0  
**Last Updated:** December 30, 2024  
**Author:** SoftCortex Development Team  
**License:** Proprietary - For Internal Use Only

---

## Appendix A: Complete File Structure

```
license-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── LicenseController.php
│   │   │   └── Admin/
│   │   │       └── LicenseManagementController.php
│   │   └── Middleware/
│   │       └── ValidateApiKey.php
│   └── Models/
│       ├── License.php
│       └── LicenseActivity.php
├── config/
│   ├── services.php
│   └── cors.php
├── database/
│   └── migrations/
│       ├── create_licenses_table.php
│       └── create_license_activities_table.php
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
│   └── Feature/
│       └── LicenseVerificationTest.php
├── .env.example
├── composer.json
└── README.md
```

---

## Appendix B: Environment Variables Reference

```env
# Application
APP_NAME="License API"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://license-api.yourcompany.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=license_api
DB_USERNAME=root
DB_PASSWORD=

# Envato
ENVATO_PERSONAL_TOKEN=your-token-here

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info

# API
API_RATE_LIMIT=60
API_KEY=your-secret-api-key

# Webhook (optional)
WEBHOOK_URL=https://yourcompany.com/webhooks/license
```

---

**END OF DOCUMENT**
