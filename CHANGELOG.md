# Changelog

All notable changes to `magic-installer` will be documented in this file.

## [Unreleased]

### Changed
- **BREAKING**: Migrated from database-based storage to file-based storage for installer state
  - Installation status now tracked in `storage/app/.installed` file
  - Installer settings now stored in `storage/app/installer-settings.json`
  - Removed database dependencies from installer core
  - The `settings` table migration is still included but no longer used by the installer

### Fixed
- Fixed chicken-and-egg problem where installer tried to access database before it was configured
- Fixed "Table 'database_name.settings' doesn't exist" error during initial setup
- Fixed "Class 'SoftCortex\\Installer\\Services\\DB' not found" error

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
