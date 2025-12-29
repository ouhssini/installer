# Changelog

All notable changes to `magic-installer` will be documented in this file.

## [Unreleased]

### Added

- Added new "App Configuration" step in installer
  - Configure APP_NAME, APP_ENV, APP_DEBUG, APP_URL, APP_TIMEZONE, APP_LOCALE
  - Auto-detects available languages from lang directory
  - Supports both folder-based (en/, fr/) and JSON-based (en.json) locales
  - Provides 40+ common timezones
  - Toggle switch for debug mode
  - Environment selection (local, development, staging, production)
  - Step 2 of 7 in installation flow
- Added AppConfigController with locale and timezone detection
- Added app-config.blade.php view with modern UI

### Changed

- Updated installation flow to 7 steps (was 6)
  - Step 1: Welcome
  - Step 2: App Configuration (NEW)
  - Step 3: Requirements
  - Step 4: Database
  - Step 5: License
  - Step 6: Admin
  - Step 7: Finalize
- Updated all controllers to reflect new step numbers

### Added (Previous)

- Added automatic `.env` file initialization from `.env.example`
  - Installer creates `.env` from `.env.example` if it doesn't exist
  - Automatically generates new `APP_KEY` during first step
  - Clears config/route/cache after initialization
  - No manual `.env` setup required
- Added `.env.example` with non-database queue/session drivers
  - Uses `file` for session driver (not database)
  - Uses `sync` for queue driver (not database)
  - Ready to use without database configuration
- Added `initializeFromExample()` method to EnvironmentManager
- Added `generateAppKey()` method to EnvironmentManager
- Added `envFileExists()` method to EnvironmentManager

### Changed

- Updated WelcomeController to handle `.env` initialization on first step

## v1.1.0 - 2025-12-29

### Changelog

##### Changed

- **BREAKING**: Middleware now registers globally - no manual configuration needed
  - All routes automatically protected when not installed
  - Accessing any route (including `/`) redirects to installer
  - Removed need for manual middleware registration in `bootstrap/app.php`
  

##### Added

- Added database sync after installation completes
  - Installation data synced to `settings` table when database is available
  - Hybrid approach: file storage during install, database after
  
- Added auto-login after installation
  - Admin user automatically authenticated after finalization
  - Seamless transition from installation to application
  

#### fix DB errors - 2025-12-29

**Full Changelog**: https://github.com/ouhssini/installer/compare/v1.0.1...v1.0.2

#### v1.0.1 fix the settings_table_access_error - 2025-12-29

**Full Changelog**: https://github.com/ouhssini/installer/compare/v1.0.0...v1.0.1

##### Changed

- **BREAKING**: Migrated from database-based storage to file-based storage for installer state
  - Installation status now tracked in `storage/app/.installed` file
  - Installer settings now stored in `storage/app/installer-settings.json`
  - Removed database dependencies from installer core
  - The `settings` table migration is still included but no longer used by the installer
  

##### Fixed

- Fixed chicken-and-egg problem where installer tried to access database before it was configured
- Fixed "Table 'database_name.settings' doesn't exist" error during initial setup
- Fixed "Class 'SoftCortex\Installer\Services\DB' not found" error

##### Added

- Added `clearInstallerData()` method to remove all installer files
- Added comprehensive property-based tests (7427 tests with 39300 assertions)
- Added installation date tracking in finalization step

#### [1.0.1] - 2024-01-XX

##### Fixed

- Fixed Packagist validation errors with invalid keywords
- Deleted problematic branch
- Updated PHP version requirement from ^8.4 to ^8.2 for broader compatibility

##### Changed

- Improved README.md with better formatting and structure
- Added professional description and SEO-optimized keywords to composer.json

#### [1.0.0] - 2024-01-XX

##### Added

- Initial release
- Multi-step installation wizard
- Requirements checking
- Database configuration
- License verification (Envato)
- Admin account creation
- Installation finalization
- Middleware for installation protection
- Comprehensive test suite

## [Unreleased]

### Changed

- **BREAKING**: Middleware now registers globally - no manual configuration needed
  - All routes automatically protected when not installed
  - Accessing any route (including `/`) redirects to installer
  - Removed need for manual middleware registration in `bootstrap/app.php`
  

### Added

- Added database sync after installation completes
  - Installation data synced to `settings` table when database is available
  - Hybrid approach: file storage during install, database after
  
- Added auto-login after installation
  - Admin user automatically authenticated after finalization
  - Seamless transition from installation to application
  

## fix DB errors - 2025-12-29

**Full Changelog**: https://github.com/ouhssini/installer/compare/v1.0.1...v1.0.2

## v1.0.1 fix the settings_table_access_error - 2025-12-29

**Full Changelog**: https://github.com/ouhssini/installer/compare/v1.0.0...v1.0.1

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
- Added comprehensive property-based tests (7427 tests with 39300 assertions)
- Added installation date tracking in finalization step

## [1.0.1] - 2024-01-XX

### Fixed

- Fixed Packagist validation errors with invalid keywords
- Deleted problematic branch
- Updated PHP version requirement from ^8.4 to ^8.2 for broader compatibility

### Changed

- Improved README.md with better formatting and structure
- Added professional description and SEO-optimized keywords to composer.json

## [1.0.0] - 2024-01-XX

### Added

- Initial release
- Multi-step installation wizard
- Requirements checking
- Database configuration
- License verification (Envato)
- Admin account creation
- Installation finalization
- Middleware for installation protection
- Comprehensive test suite
