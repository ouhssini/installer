# Laravel Envato Installer Wizard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/softcortex/magic-installer.svg?style=flat-square)](https://packagist.org/packages/softcortex/magic-installer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/softcortex/magic-installer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/softcortex/magic-installer/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/softcortex/magic-installer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/softcortex/magic-installer/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/softcortex/magic-installer.svg?style=flat-square)](https://packagist.org/packages/softcortex/magic-installer)

A professional WordPress-like installer wizard for Laravel 11+ applications with built-in Envato purchase code verification. Perfect for CodeCanyon products and commercial Laravel applications.

## Features

- 🎨 **Beautiful UI** - Clean, modern interface with TailwindCSS
- 🔐 **Envato Integration** - Real Envato API purchase code verification
- ✅ **Requirements Check** - Automatic PHP version, extensions, and permissions validation
- 🗄️ **Database Setup** - Interactive database configuration with connection testing
- 👤 **Admin Creation** - Secure admin account setup with role assignment
- 🔒 **Security First** - CSRF protection, input validation, password hashing
- 🚀 **One-Click Install** - Complete installation in minutes
- 🔓 **Recovery Command** - Unlock installer if needed

## Requirements

- PHP 8.2 or higher
- Laravel 11 or 12
- Composer

## Installation

Install the package via composer:

```bash
composer require softcortex/magic-installer
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="installer-config"
```

Publish the migration:

```bash
php artisan vendor:publish --tag="installer-migrations"
php artisan migrate
```

Optionally, publish the views for customization:

```bash
php artisan vendor:publish --tag="installer-views"
```

## Configuration

### 1. Envato Personal Token Setup

To use Envato purchase code verification, you need to create a Personal Token:

1. Go to [https://build.envato.com/create-token/](https://build.envato.com/create-token/)
2. Create a new token with these permissions:
   - ✅ **View and search Envato sites**
   - ✅ **View the user's account username**
3. Copy the generated token
4. Add it to your `.env` file:

```env
ENVATO_PERSONAL_TOKEN=your-personal-token-here
ENVATO_ITEM_ID=your-item-id-here  # Optional: Your CodeCanyon item ID
```

### 2. Environment Configuration

Add these variables to your `.env` file:

```env
# License Configuration
LICENSE_ENABLED=true
ENVATO_PERSONAL_TOKEN=your-personal-token-here
ENVATO_ITEM_ID=12345678  # Optional

# Application
APP_NAME="Your Application Name"
APP_URL=http://localhost
```

### 3. Middleware Setup

The package automatically registers middleware. Add the `EnsureInstalled` middleware to your `bootstrap/app.php`:

```php
use SoftCortex\Installer\Http\Middleware\EnsureInstalled;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureInstalled::class,
        ]);
    })
    ->create();
```

## Usage

### Installation Process

1. Navigate to `/install` in your browser
2. Follow the step-by-step wizard:
   - **Welcome** - Introduction and license agreement
   - **Requirements** - System requirements check
   - **Database** - Database configuration and testing
   - **License** - Envato purchase code verification
   - **Admin** - Create admin account
   - **Finalize** - Complete installation

### Purchase Code Verification

The installer uses the official Envato API to verify purchase codes:

- Purchase codes must be in UUID format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
- Validates against Envato's database in real-time
- Stores only a SHA-256 hash (never the actual code)
- Retrieves license information: item name, buyer, purchase date, support expiry

### Unlocking the Installer

If you need to re-run the installer:

```bash
php artisan installer:unlock
```

Or force unlock without confirmation:

```bash
php artisan installer:unlock --force
```

## Configuration Options

Edit `config/installer.php` to customize:

```php
return [
    // Product information
    'product' => [
        'name' => env('APP_NAME', 'Laravel Application'),
        'version' => '1.0.0',
        'description' => 'Professional Laravel application',
    ],
    
    // Server requirements
    'requirements' => [
        'php' => '8.2',
        'extensions' => [
            'pdo', 'openssl', 'mbstring', 'tokenizer',
            'json', 'curl', 'xml', 'ctype', 'fileinfo',
        ],
        'directories' => [
            'storage', 'storage/app', 'storage/framework',
            'storage/logs', 'bootstrap/cache',
        ],
    ],
    
    // License verification
    'license' => [
        'enabled' => env('LICENSE_ENABLED', true),
        'envato_personal_token' => env('ENVATO_PERSONAL_TOKEN', ''),
        'envato_item_id' => env('ENVATO_ITEM_ID', ''),
    ],
    
    // Routes
    'routes' => [
        'prefix' => 'install',
        'middleware' => ['web', 'installer.redirect'],
        'redirect_after_install' => 'dashboard',
    ],
    
    // Admin role
    'admin' => [
        'role' => 'admin',
        'create_role_if_missing' => true,
    ],
];
```

## Customization

### Views

Publish and customize the views:

```bash
php artisan vendor:publish --tag="installer-views"
```

Views will be available in `resources/views/vendor/installer/`.

### Styling

The installer uses TailwindCSS. You can customize the styles by:

1. Publishing the views
2. Modifying the Tailwind classes
3. Or including your own CSS in the layout

### Routes

Change the installer route prefix in `config/installer.php`:

```php
'routes' => [
    'prefix' => 'setup',  // Changes route to /setup
],
```

## Security

### Best Practices

- ✅ Always use HTTPS in production
- ✅ Keep your Envato Personal Token secure
- ✅ Never commit `.env` file to version control
- ✅ Use strong passwords for admin accounts
- ✅ Run `installer:unlock` only when necessary

### What's Stored

The installer stores:
- ✅ SHA-256 hash of purchase code (not the actual code)
- ✅ License metadata (item name, buyer, dates)
- ✅ Installation status
- ❌ Never stores: Envato Personal Token, plain purchase codes

## Troubleshooting

### "Envato Personal Token not configured"

**Solution:** Add `ENVATO_PERSONAL_TOKEN` to your `.env` file.

### "Invalid purchase code format"

**Solution:** Envato purchase codes are UUIDs. Format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`

### "License verification failed: 401"

**Solution:** Your Personal Token is invalid or expired. Create a new one at [https://build.envato.com/create-token/](https://build.envato.com/create-token/)

### "Purchase code not found"

**Solution:** The purchase code doesn't exist in Envato's database. Verify the code is correct.

### Database Connection Failed

**Solution:** 
- Check database credentials
- Ensure database server is running
- Verify database exists
- Check firewall settings

### Permission Errors

**Solution:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Testing

Run the test suite:

```bash
composer test
```

Run specific test types:

```bash
# Property-based tests
vendor/bin/pest --group=property

# Unit tests
vendor/bin/pest --group=unit

# Integration tests
vendor/bin/pest --group=integration
```

## API Reference

### LicenseService

```php
use SoftCortex\Installer\Services\LicenseService;

$licenseService = app(LicenseService::class);

// Verify purchase code
$result = $licenseService->verify('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');

if ($result->isValid()) {
    echo "Item: " . $result->itemName;
    echo "Buyer: " . $result->buyerName;
    echo "License: " . $result->licenseType; // 'regular' or 'extended'
    echo "Support Until: " . $result->supportedUntil;
} else {
    echo "Error: " . $result->getError();
}

// Get stored license data
$license = $licenseService->getLicense();
```

### InstallerService

```php
use SoftCortex\Installer\Services\InstallerService;

$installer = app(InstallerService::class);

// Check installation status
if ($installer->isInstalled()) {
    // Application is installed
}

// Get/Set settings
$installer->setSetting('key', 'value');
$value = $installer->getSetting('key');
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [SoftCortex](https://github.com/Softcortex)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
