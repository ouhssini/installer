# Laravel Envato Installer Wizard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/softcortex/magic-installer.svg?style=flat-square)](https://packagist.org/packages/softcortex/magic-installer)
[![Total Downloads](https://img.shields.io/packagist/dt/softcortex/magic-installer.svg?style=flat-square)](https://packagist.org/packages/softcortex/magic-installer)
[![License](https://img.shields.io/packagist/l/softcortex/magic-installer.svg?style=flat-square)](https://packagist.org/packages/softcortex/magic-installer)

A professional WordPress-like installer wizard for Laravel 11+ applications with built-in Envato purchase code verification. Perfect for CodeCanyon products and commercial Laravel applications.

## ✨ Features

- 🎨 **Beautiful UI** - Clean, modern interface with TailwindCSS
- 🔐 **Envato Integration** - Real Envato API v3 purchase code verification
- ✅ **Requirements Check** - Automatic PHP version, extensions, and permissions validation
- 🗄️ **Database Setup** - Interactive database configuration with connection testing
- 👤 **Admin Creation** - Secure admin account setup with role assignment
- 🔒 **Security First** - CSRF protection, input validation, password hashing
- 🚀 **One-Click Install** - Complete installation in minutes
- 🔓 **Recovery Command** - Unlock installer if needed

## 📋 Requirements

- PHP 8.2 or higher
- Laravel 11 or 12
- Composer
- Writable `storage/app` directory
- MySQL/PostgreSQL/SQLite database

## 📦 Installation

Install the package via Composer:

```bash
composer require softcortex/magic-installer
```

> **Note**: The installer uses file-based storage (`storage/app/.installed` and `storage/app/installer-settings.json`) and does NOT require database setup before running. This means it works immediately after `composer require` without any database configuration.

### Publish Assets

Publish the configuration file:

```bash
php artisan vendor:publish --tag="installer-config"
```

Optionally, publish the views for customization:

```bash
php artisan vendor:publish --tag="installer-views"
```

**Or publish everything at once:**

```bash
php artisan vendor:publish --provider="SoftCortex\Installer\InstallerServiceProvider"
```

## ⚙️ Configuration

### Storage

The installer uses file-based storage to avoid database dependency issues:

- **Installation Status**: `storage/app/.installed`
- **Settings**: `storage/app/installer-settings.json`

This approach ensures the installer works before database configuration, eliminating the chicken-and-egg problem.

### 1. Envato Personal Token Setup

To enable Envato purchase code verification:

1. Visit [https://build.envato.com/create-token/](https://build.envato.com/create-token/)
2. Create a token with these permissions:
   - ✅ View and search Envato sites
   - ✅ View the user's account username
3. Add to your `.env` file:

```env
LICENSE_ENABLED=true
ENVATO_PERSONAL_TOKEN=your-personal-token-here
ENVATO_ITEM_ID=12345678  # Optional: Your CodeCanyon item ID
```

### 2. Middleware Setup

Add the `EnsureInstalled` middleware to `bootstrap/app.php`:

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

### 3. Configuration Options

Customize `config/installer.php`:

```php
return [
    'product' => [
        'name' => env('APP_NAME', 'Laravel Application'),
        'version' => '1.0.0',
    ],
    
    'requirements' => [
        'php' => '8.2',
        'extensions' => ['pdo', 'openssl', 'mbstring', /* ... */],
    ],
    
    'license' => [
        'enabled' => env('LICENSE_ENABLED', true),
        'envato_personal_token' => env('ENVATO_PERSONAL_TOKEN', ''),
    ],
    
    'routes' => [
        'prefix' => 'install',
        'redirect_after_install' => 'dashboard',
    ],
];
```

## 🚀 Usage

### Installation Wizard

1. Navigate to `/install` in your browser
2. Follow the 6-step wizard:
   - **Welcome** - Introduction
   - **Requirements** - System check
   - **Database** - Database setup
   - **License** - Purchase code verification
   - **Admin** - Create admin account
   - **Finalize** - Complete installation

### Purchase Code Format

Envato purchase codes must be in UUID format:
```
xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
```

The installer:
- ✅ Validates against Envato API in real-time
- ✅ Stores only SHA-256 hash (never the actual code)
- ✅ Retrieves license info: item name, buyer, dates, support expiry

### Unlock Installer

Re-run the installer if needed:

```bash
php artisan installer:unlock
```

Force unlock without confirmation:

```bash
php artisan installer:unlock --force
```

## 🎨 Customization

### Views

Publish and modify views:

```bash
php artisan vendor:publish --tag="installer-views"
```

Views location: `resources/views/vendor/installer/`

### Routes

Change the installer URL prefix in `config/installer.php`:

```php
'routes' => [
    'prefix' => 'setup',  // URL becomes /setup
],
```

### Styling

The installer uses TailwindCSS. Customize by:
1. Publishing views
2. Modifying Tailwind classes
3. Adding custom CSS to the layout

## 🔒 Security

### Best Practices

- ✅ Always use HTTPS in production
- ✅ Keep Envato Personal Token secure (never commit to Git)
- ✅ Use strong admin passwords
- ✅ Run `installer:unlock` only when necessary

### Data Storage

**File-based storage:**
- ✅ Installation status in `storage/app/.installed`
- ✅ Settings in `storage/app/installer-settings.json`
- ✅ SHA-256 hash of purchase code
- ✅ License metadata (item name, buyer, dates)

**Never stored:**
- ❌ Envato Personal Token
- ❌ Plain text purchase codes

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| "Envato Personal Token not configured" | Add `ENVATO_PERSONAL_TOKEN` to `.env` |
| "Invalid purchase code format" | Use UUID format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx` |
| "License verification failed: 401" | Token expired - create new one at [build.envato.com](https://build.envato.com/create-token/) |
| "Purchase code not found" | Verify code is correct in Envato |
| Database connection failed | Check credentials, server status, firewall |
| Permission errors | Ensure `storage/app` directory is writable: `chmod -R 775 storage` |
| "Settings table not found" | This error should not occur with v1.1.0+ (uses file storage) |

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run specific test types:

```bash
vendor/bin/pest --group=property  # Property-based tests
vendor/bin/pest --group=unit      # Unit tests
```

## 📚 API Reference

### LicenseService

```php
use SoftCortex\Installer\Services\LicenseService;

$service = app(LicenseService::class);
$result = $service->verify('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');

if ($result->isValid()) {
    echo $result->itemName;
    echo $result->buyerName;
    echo $result->licenseType; // 'regular' or 'extended'
}
```

### InstallerService

```php
use SoftCortex\Installer\Services\InstallerService;

$installer = app(InstallerService::class);

if ($installer->isInstalled()) {
    // App is installed
}

$installer->setSetting('key', 'value');
$value = $installer->getSetting('key', 'default');
```

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for recent changes.

## 🤝 Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## 🔐 Security

Report security vulnerabilities via [our security policy](../../security/policy).

## 👥 Credits

- [SoftCortex](https://github.com/Softcortex)
- [All Contributors](../../contributors)

## 📄 License

MIT License. See [LICENSE.md](LICENSE.md) for details.
