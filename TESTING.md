# Testing the Package

This document explains how to test the Laravel Envato Installer Wizard package.

## Running Tests in Development

The package includes a comprehensive test suite with 7,400+ tests covering all functionality.

### Run All Tests

```bash
vendor/bin/pest
```

### Run Specific Test Suites

```bash
# Property-based tests
vendor/bin/pest tests/Property

# Unit tests
vendor/bin/pest tests/Unit

# Service provider tests
vendor/bin/pest tests/Unit/ServiceProviderTest.php
```

### Test Coverage

```bash
vendor/bin/pest --coverage
```

## Testing Publishing Commands

**Important:** The `vendor:publish` commands only work when the package is installed in a Laravel application via Composer, not in the package development environment.

### Why Publishing Doesn't Work in Development

When you run:
```bash
php artisan vendor:publish --tag="installer-config"
```

You get an error because:
1. There's no `artisan` file in the package root (packages don't have one)
2. Publishing is designed to work from a Laravel application that has installed the package
3. The package needs to be in the `vendor/` directory of a Laravel app

### How to Test Publishing

#### Option 1: Create a Test Laravel Application

1. Create a new Laravel application:
```bash
composer create-project laravel/laravel test-app
cd test-app
```

2. Add your package as a local repository in `composer.json`:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../installer"
        }
    ]
}
```

3. Require the package:
```bash
composer require softcortex/magic-installer
```

4. Now test the publishing commands:
```bash
php artisan vendor:publish --tag="installer-config"
php artisan vendor:publish --tag="installer-migrations"
php artisan vendor:publish --tag="installer-views"
```

#### Option 2: Use Orchestra Testbench

The package already uses Orchestra Testbench for testing. The service provider tests verify that all publishable assets are correctly configured:

```bash
vendor/bin/pest tests/Unit/ServiceProviderTest.php
```

These tests verify:
- ✅ Config file is publishable
- ✅ Migration file is publishable
- ✅ Views are publishable
- ✅ Routes are registered
- ✅ Middleware is registered
- ✅ Services are registered

## Verifying Package Structure

### Check All Required Files Exist

```bash
# Config file
ls config/installer.php

# Migration
ls database/migrations/create_settings_table.php.stub

# Views
ls resources/views/*.blade.php

# Routes
ls routes/installer.php
```

### Verify Service Provider Configuration

The service provider is configured to publish:

1. **Config** - Tagged as `installer-config`
   - Source: `config/installer.php`
   - Destination: `config/installer.php` in Laravel app

2. **Migration** - Tagged as `installer-migrations`
   - Source: `database/migrations/create_settings_table.php.stub`
   - Destination: `database/migrations/{timestamp}_create_settings_table.php` in Laravel app

3. **Views** - Tagged as `installer-views`
   - Source: `resources/views/`
   - Destination: `resources/views/vendor/installer/` in Laravel app

## Testing in a Real Laravel Application

### Step 1: Install the Package

```bash
composer require softcortex/magic-installer
```

### Step 2: Publish Assets

```bash
# Publish config
php artisan vendor:publish --tag="installer-config"

# Publish migrations
php artisan vendor:publish --tag="installer-migrations"

# Run migrations
php artisan migrate

# Optionally publish views
php artisan vendor:publish --tag="installer-views"
```

### Step 3: Configure Environment

Add to `.env`:
```env
ENVATO_PERSONAL_TOKEN=your-token-here
ENVATO_ITEM_ID=your-item-id
```

### Step 4: Test the Installer

1. Visit `http://your-app.test/install`
2. Follow the installation wizard
3. Verify each step works correctly

### Step 5: Test Recovery

```bash
php artisan installer:unlock
```

## Continuous Integration

The package includes GitHub Actions workflows for:
- Running tests on PHP 8.2, 8.3, 8.4
- Running tests on Laravel 11 and 12
- Code style checks with Laravel Pint
- Static analysis with PHPStan

## Property-Based Testing

The package uses property-based testing to verify correctness properties across 100+ random inputs per test. This ensures:

- License verification works with any valid UUID format
- Database configuration preserves environment variables
- Admin creation works with any valid email/password
- Middleware correctly blocks/allows access
- And 30+ other correctness properties

## Manual Testing Checklist

When testing in a real Laravel application:

- [ ] Welcome page displays product information
- [ ] Requirements check validates PHP version and extensions
- [ ] Database configuration tests connection before saving
- [ ] License verification works with valid Envato purchase codes
- [ ] Admin account creation assigns correct role
- [ ] Installation completes and redirects to dashboard
- [ ] Middleware blocks access to app before installation
- [ ] Middleware allows access to app after installation
- [ ] Unlock command resets installation state
- [ ] All forms have CSRF protection
- [ ] Validation errors display without losing input
- [ ] Error messages don't expose sensitive information

## Troubleshooting

### "Could not open input file: artisan"

This error occurs when trying to run `php artisan` commands in the package directory. This is expected - packages don't have an `artisan` file. Use the testing methods described above instead.

### "Class not found" errors

Make sure you've run:
```bash
composer dump-autoload
```

### Tests failing

1. Check PHP version (requires 8.2+)
2. Run `composer install`
3. Clear test cache: `rm -rf .phpunit.cache`
4. Run tests again: `vendor/bin/pest`

## Additional Resources

- [Spatie Package Tools Documentation](https://github.com/spatie/laravel-package-tools)
- [Orchestra Testbench Documentation](https://packages.tools/testbench)
- [Pest PHP Documentation](https://pestphp.com)
