# Changelog

All notable changes to `magic-installer` will be documented in this file.

## v1.0.1 fix the settings_table_access_error - 2025-12-29

**Full Changelog**: https://github.com/ouhssini/installer/compare/v1.0.0...v1.0.1

## [Unreleased]

### Fixed

- Fixed database table access issue during initial setup - `InstallerService` now checks if settings table exists before querying it, preventing "Table not found" errors when accessing installer routes before running migrations
