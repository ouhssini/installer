# Magic Installer - AI Coding Agent Instructions

## Project Overview

**Laravel Installer Wizard Package** (softcortex/magic-installer) - A WordPress-like installation wizard for Laravel 11/12 applications with Envato purchase code verification. Built as a reusable Composer package for CodeCanyon products.

**Key Architecture Decision**: File-based storage (`storage/app/.installed`, `storage/app/installer-settings.json`) eliminates the chicken-and-egg problem of requiring database setup before installation. Database sync happens post-installation.

## Project Structure

- **Core Services** (`src/Services/`): Five singleton services handle all business logic
  - `InstallerService`: Installation state, step tracking, file-based storage operations
  - `EnvironmentManager`: `.env` file manipulation, always uses package's `.env.example` (not Laravel's)
  - `DatabaseManager`: Connection testing, migration running
  - `LicenseService`: Envato API v3 integration, stores SHA-256 hash only (never plain purchase codes)
  - `RequirementsChecker`: PHP version, extensions, directory permissions validation

- **Installation Flow**: 7-step wizard (Welcome → App Config → Requirements → Database → License → Admin → Finalize)
  - Step 1: Welcome - Introduction and installer start, redirects to App Config
  - Step 2: App Config - Creates .env from package `.env.example`, generates `APP_KEY`, sets app details (name, URL, timezone, locale)
  - Step 3: Requirements - PHP version, extensions, directory permissions check
  - Step 4: Database - Database configuration, connection test, saves credentials to .env, **runs migrations**, **creates settings table using Schema builder**
  - Step 5: License - Envato purchase code validation (optional, skipped if `LICENSE_ENABLED=false`)
  - Step 6: Admin - Create administrator account (requires migrations to have run)
  - Step 7: Finalize - Switches to database drivers, **syncs installation data to database**, locks installer, redirects to application
  - **Step Validation**: Each controller checks if previous steps are completed via `isStepCompleted()` - redirects to correct step if accessing out of order
  - Global middleware (`EnsureInstalled`) redirects all non-installer routes to `/install` until completed
  - Controllers follow pattern: `index()` shows form (with step validation), `store()` saves data and advances step

- **Testing**: Pest with property-based tests (`tests/Property/`) for high assertion coverage (39K+ assertions)
  - Each feature has 100-repeat property tests generating random valid inputs
  - Always clean up `storage/app/.installed` and `installer-settings.json` in `beforeEach`/`afterEach`

## Critical Conventions

### Middleware Architecture (CRITICAL!)
- **Installer uses custom middleware group WITHOUT `EncryptCookies`**: Prevents `MissingAppKeyException` before APP_KEY is generated
- Custom `installer` middleware group includes: `ValidatePostSize`, `ConvertEmptyStringsToNull`, `SubstituteBindings`, `RedirectIfInstalled`
- **Never use `web` middleware for installer routes** - it requires APP_KEY for cookie encryption
- **Installer views must be stateless** (no CSRF, no old(), no $errors) - validation handled explicitly via controller
- Global `EnsureInstalled` middleware on `web` group redirects non-installer routes until installation completes

### Environment Management
- **Package `.env.example` is authoritative**: Always use `__DIR__.'/../../.env.example'` in `EnvironmentManager`
- Database drivers (session, cache, queue) switched automatically on finalize via `switchToDatabaseDrivers()`
- Never manually edit `.env` - use `EnvironmentManager::set()` or `setMultiple()`

### License Verification
- Purchase codes must be UUID format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx` (validated by regex)
- API endpoint: `https://api.envato.com/v3/market/author/sale?code={code}`
- Requires Bearer token from `https://build.envato.com/create-token/`
- Store only: `license_hash` (SHA-256), `license_data` (JSON metadata) - **never the purchase code**
- **Dev Mode**: Set `LICENSE_DEV_MODE=true` and use test code `dev-test-code-12345678-1234` to bypass Envato API in development
- **Skip License**: Set `LICENSE_ENABLED=false` to skip license step entirely

### Service Provider Patterns
```php
// Services registered as singletons
$this->app->singleton(InstallerService::class);

// Middleware registration
$router->aliasMiddleware('installer.ensure', EnsureInstalled::class);
$router->pushMiddlewareToGroup('web', EnsureInstalled::class);
```

### Storage Operations
```php
// Always use file storage during installation
$this->installedFilePath = storage_path('app/.installed');
$this->settingsFilePath = storage_path('app/installer-settings.json');

// Database sync happens post-installation in finalize()
$this->syncToDatabase(); // Optional, fails silently if table missing
```

## Development Workflows

### Running Tests
```bash
composer test                    # Full Pest suite
vendor/bin/pest --group=property # Property tests only
vendor/bin/pest --group=unit     # Unit tests only
composer test-coverage           # With coverage
```

### Quality Tools
```bash
composer analyse  # PHPStan (level 9, strict rules)
composer format   # Laravel Pint (PSR-12 + Laravel conventions)
```

### Reset Installation (Common During Dev)
```bash
php artisan installer:unlock --force
# Or manually delete: storage/app/.installed and installer-settings.json
```

### Package Publishing
```bash
php artisan vendor:publish --tag="installer-config"   # Config only
php artisan vendor:publish --tag="installer-views"    # Views for customization
php artisan vendor:publish --tag="installer-env"      # Package .env.example
php artisan vendor:publish --tag="installer-migration"   # Publish migration file (needed to add if not)
```

## Common Pitfalls

1. **Don't use Laravel's `.env.example`** - Package has its own with database drivers preconfigured
2. **Never check `Schema::hasTable()` during early installation** - Database might not be connected yet
3. **Wrap `Artisan::call('cache:clear')` in try-catch** - Testing environments may not have cache tables
4. **Purchase code validation**: Always validate UUID format before calling Envato API to avoid 404s
5. **HTTP fakes in tests**: Envato API responses must include `item` object with `id` and `name`
6. **Step validation**: All controllers must check `isStepCompleted()` for previous steps before rendering views
7. **Database table creation**: Always use `DB::getSchemaBuilder()->create()` instead of raw SQL for database-agnostic compatibility
8. **Settings table structure**: Include `category` and `changeable` columns - check column existence before inserting

## Integration Points

- **Spatie Laravel Package Tools**: Used for provider boilerplate (`Package` class)
- **Orchestra Testbench**: Testing harness for Laravel packages
- **TailwindCSS**: All views use Tailwind classes (embedded in Blade files)
- **Envato API v3**: Real-time license verification with 15-second timeout

## Key Files to Reference

- [InstallerService.php](src/Services/InstallerService.php) - File storage patterns, step tracking
- [EnvironmentManager.php](src/Services/EnvironmentManager.php) - `.env` manipulation with quoting logic
- [LicenseService.php](src/Services/LicenseService.php) - Envato API integration, error handling
- [EnsureInstalled.php](src/Http/Middleware/EnsureInstalled.php) - Global redirect logic
- [installer.php config](config/installer.php) - All configurable options
- [Property tests](tests/Property/) - Examples of valid input generation and assertions

## Code Style Notes

- Use constructor property promotion for dependency injection
- Prefer `isset()` over `array_key_exists()` for performance
- Silent failures with `try-catch` for cache/database operations during installation
- Always return specific error messages (never generic "An error occurred")
- Use named parameters for result objects: `new Result(valid: true, error: null)`
