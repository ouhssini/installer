# Changelog

All notable changes to `magic-installer` will be documented in this file.

## [Unreleased]

### Fixed
- Fixed database table access issue during initial setup - `InstallerService` now checks if settings table exists before querying it, preventing "Table not found" errors when accessing installer routes before running migrations
