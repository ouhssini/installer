# Changelog

All notable changes to `magic-installer` will be documented in this file.

## [Unreleased]

## [1.2.5] - 2025-01-01

### Changed
- **REVERTED**: Removed multi-database support (SQLite, PostgreSQL)
  - Simplified back to MySQL-only configuration for stability
  - Removed complex database connection switching logic
  - Restored simple, reliable MySQL-only installation flow
  - All 7,433 tests passing

### Fixed
- Fixed critical database connection issues during installation
- Fixed "Connection reset" errors when configuring database
- Fixed "Table already exists" migration errors
- Removed problematic `isInstalled()` database checking during installation

### Removed
- Removed SQLite support
- Removed PostgreSQL support
- Removed multi-database selection UI
- Removed complex connection switching logic

## [1.2.4] - 2024-12-31

### Fixed
- Fixed database step errors with connection management
- Improved error handling for database configuration
- Fixed SQLite fallback issues in createSettingsTable

## [1.2.3] - 2024-12-31

### Fixed
- Added better error handling and connection management for database setup
- Fixed explicit connection usage to prevent SQLite fallback

## [1.2.2] - 2024-12-31

### Fixed
- Corrected all step numbers in installation wizard
- Fixed progress indicator step counting

## [1.2.1] - 2024-12-31

### Fixed
- Added App Config to progress indicator
- Fixed database connection switching issues

## [1.2.0] - 2024-12-30

### Added
- Added new "App Configuration" step in installer (Step 2 of 7)
  - Configure APP_NAME, APP_ENV, APP_DEBUG, APP_URL, APP_TIMEZONE, APP_LOCALE
  - Auto-detects available languages from lang directory
  - Supports both folder-based (en/, fr/) and JSON-based (en.json) locales
  - Provides 40+ common timezones
  - Toggle switch for debug mode
  - Environment selection (local, development, staging, production)
- Added AppConfigController with locale and timezone detection
- Added app-config.blade.php view with modern UI
- Added automatic `.env` file initialization from `.env.example`
  - Installer creates `.env` from `.env.example` if it doesn't exist
  - Automatically generates new `APP_KEY` during first step
  - Clears config/route/cache after initialization
  - No manual `.env` setup required
- Added `.env.example` with non-database queue/session drivers
  - Uses `file` for session driver (not database)
  - Uses `sync` for queue driver (not database)
  - Uses `file` for cache driver (not database)
  - Ready to use without database configuration
- Added `initializeFromExample()` method to EnvironmentManager
- Added `generateAppKey()` method to EnvironmentManager
- Added `envFileExists()` method to EnvironmentManager

### Changed
- **BREAKING**: `.env` file now always initialized from package's `.env.example`
  - Ensures non-database drivers (session=file, queue=sync, cache=file)
  - Overwrites existing `.env` to guarantee correct configuration
  - MySQL set as default database connection
- Updated installation flow to 7 steps (was 6)
  - Step 1: Welcome
  - Step 2: App Configuration (NEW)
  - Step 3: Requirements
  - Step 4: Database
  - Step 5: License
  - Step 6: Admin
  - Step 7: Finalize
- Updated all controllers to reflect new step numbers
- Updated WelcomeController to handle `.env` initialization on first step

## [1.1.0] - 2024-12-29

### Added
- Added database sync after installation completes
  - Installation data synced to `settings` table when database is available
  - Hybrid approach: file storage during install, database after
- Added auto-login after installation
  - Admin user automatically authenticated after finalization
  - Seamless transition from installation to application

### Changed
- **BREAKING**: Middleware now registers globally - no manual configuration needed
  - All routes automatically protected when not installed
  - Accessing any route (including `/`) redirects to installer
  - Removed need for manual middleware registration in `bootstrap/app.php`

## [1.0.2] - 2024-12-29

### Changed
- **BREAKING**: Migrated from database-based storage to file-based storage for installer state
  - Installation status now tracked in `storage/app/.installed` file
  - Installer settings now stored in `storage/app/installer-settings.json`
  - Removed database dependencies from installer core
  - The `settings` table migration is still included but no longer used by the installer

### Fixed
- Fixed chicken-and-egg problem where installer tried to access database before it was configured
- Fixed "Table 'database_name.settings' doesn't exist" error during initial setup
- Fixed "Class 'SoftCortex\Installer\Services\DB' not found" error

### Added
- Added `clearInstallerData()` method to remove all installer files
- Added comprehensive property-based tests (7,433 tests with 39,705 assertions)
- Added installation date tracking in finalization step

## [1.0.1] - 2024-12-29

### Fixed
- Fixed Packagist validation errors with invalid keywords
- Deleted problematic branch
- Updated PHP version requirement from ^8.4 to ^8.2 for broader compatibility

### Changed
- Improved README.md with better formatting and structure
- Added professional description and SEO-optimized keywords to composer.json

## [1.0.0] - 2024-12-28

### Added
- Initial release
- Multi-step installation wizard (6 steps)
  - Step 1: Welcome
  - Step 2: Requirements checking
  - Step 3: Database configuration (MySQL)
  - Step 4: License verification (Envato)
  - Step 5: Admin account creation
  - Step 6: Installation finalization
- Requirements checking
  - PHP version validation
  - PHP extensions validation
  - Directory permissions validation
- Database configuration
  - MySQL connection testing
  - Automatic migration execution
  - Settings table creation
- License verification
  - Envato purchase code validation
  - Optional license verification
  - Configurable via config file
- Admin account creation
  - User creation with validation
  - Password hashing
  - Email validation
- Installation finalization
  - Cache clearing
  - Config clearing
  - View clearing
- Middleware for installation protection
  - Redirects to installer if not installed
  - Protects all application routes
- Comprehensive test suite
  - Unit tests
  - Property-based tests
  - Integration tests
  - 7,433 tests with 39,705 assertions
- Service providers
  - Automatic route registration
  - Automatic view registration
  - Automatic middleware registration
- Blade views
  - Modern, responsive UI
  - Progress indicator
  - Form validation
  - Error handling
- Configuration file
  - Customizable product information
  - Customizable requirements
  - Customizable license settings
  - Customizable installer settings
