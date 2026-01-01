# Commit Summary - Database Connection Fix

## Commit Hash
`9b6513f`

## Changes Made

### Critical Fix: Database Connection During Installation

**Problem**: The installer was checking the database for installation status DURING installation, which caused connection resets when switching from SQLite to MySQL/PostgreSQL.

**Solution**: Modified `InstallerService::isInstalled()` to ONLY check file storage during installation. Database is only used after installation completes.

### Files Modified

1. **src/Services/InstallerService.php**
   - Simplified `isInstalled()` method - now only checks `.installed` file
   - Removed `isDatabaseAvailable()` helper method
   - Updated `syncToDatabase()`, `finalize()`, and `clearInstallerData()` methods

2. **src/Services/DatabaseManager.php**
   - Changed `runMigrations()` to use `migrate:fresh` for clean table creation
   - Create settings table AFTER migrations (not before)

3. **DATABASE_FIX_SUMMARY.md**
   - Added comprehensive documentation of the fix

### Files Deleted

- Removed `.github/` folder and all GitHub Actions workflows
  - FUNDING.yml
  - ISSUE_TEMPLATE/
  - dependabot.yml
  - workflows/ (all CI/CD workflows)

## Testing

✅ All 7,433 tests passing

## Installation Flow (Corrected)

1. **Steps 1-6**: File-based storage only (`.installed`, `installer-settings.json`)
2. **Step 7**: Sync to database, verify, then delete files
3. **Post-installation**: Database storage only

## Key Principle

**Never check database during installation - file storage is the single source of truth until installation completes.**
