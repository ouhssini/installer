# Requirements Document

## Introduction

This document specifies the requirements for a professional, reusable installer wizard package for Laravel 12 applications intended for sale on Envato marketplaces (CodeCanyon/ThemeForest). The installer provides a WordPress-like installation experience with multi-step wizard flow, server requirements checking, database configuration, license verification, and admin account setup. The installer is never removed after installation; instead, access is controlled via middleware based on installation state stored in the database.

## Glossary

- **Installer**: The Laravel package that provides the installation wizard functionality
- **Host_Application**: The Laravel application that uses the Installer package
- **Installation_State**: A database flag indicating whether the application has been installed
- **Settings_Table**: Database table storing key-value why not using the magic installer table so that there is no missconfig configuration pairs including installation state
- **EnsureInstalled_Middleware**: Middleware that blocks access to the application until installation is complete
- **RedirectIfInstalled_Middleware**: Middleware that blocks access to installer routes after installation
- **License_Verification_API**: External API service that validates Envato purchase codes
- **Purchase_Code**: Unique code provided by Envato to verify legitimate purchases
- **Installer_Service**: Service class handling installation logic and state management
- **License_Service**: Service class handling license verification with external API

## Requirements

### Requirement 1: Installation State Management

**User Story:** As a system administrator, I want the installation state to be persisted in the database, so that the application remembers its installation status across deployments and updates.

#### Acceptance Criteria

1. THE Installer SHALL create a Settings_Table with columns for key (string) and value (text)
2. WHEN installation is complete, THE Installer SHALL set the app_installed key to "true" in the Settings_Table
3. WHEN checking installation status, THE Installer_Service SHALL query the Settings_Table for the app_installed key
4. IF the app_installed key does not exist, THEN THE Installer_Service SHALL treat the application as not installed
5. THE Settings_Table SHALL persist across application updates and deployments

### Requirement 2: Access Control via Middleware

**User Story:** As a product author, I want access to the application and installer to be controlled by middleware, so that users cannot access the application before installation or access the installer after installation.

#### Acceptance Criteria

1. THE Installer SHALL provide an EnsureInstalled_Middleware class
2. WHEN a request is made to the Host_Application and installation is not complete, THEN THE EnsureInstalled_Middleware SHALL redirect to the installer welcome page
3. WHEN a request is made to installer routes, THEN THE EnsureInstalled_Middleware SHALL allow the request to proceed
4. THE Installer SHALL provide a RedirectIfInstalled_Middleware class
5. WHEN a request is made to installer routes and installation is complete, THEN THE RedirectIfInstalled_Middleware SHALL redirect to the application dashboard
6. THE Installer SHALL register both middleware classes with Laravel's middleware system

### Requirement 3: Multi-Step Wizard Flow

**User Story:** As an Envato buyer, I want to complete installation through a guided multi-step wizard, so that I can easily set up the application without technical expertise.

#### Acceptance Criteria

1. THE Installer SHALL provide a welcome step displaying product name, version, and description
2. THE Installer SHALL provide a server requirements check step that validates PHP version, extensions, and directory permissions
3. WHEN server requirements are not met, THEN THE Installer SHALL prevent progression to the next step
4. THE Installer SHALL provide a database configuration step with inputs for host, name, username, and password
5. THE Installer SHALL provide a license verification step with input for Envato purchase code
6. THE Installer SHALL provide an admin account setup step with inputs for name, email, and password
7. THE Installer SHALL provide a finalization step that completes installation and redirects to the dashboard
8. WHEN a step is completed, THE Installer SHALL allow navigation to the next step
9. THE Installer SHALL display progress indication showing current step and completed steps

### Requirement 4: Server Requirements Validation

**User Story:** As a system administrator, I want the installer to validate server requirements before proceeding, so that I know the application will run correctly on my server.

#### Acceptance Criteria

1. THE Installer SHALL check that PHP version meets the minimum requirement (8.2+)
2. THE Installer SHALL check that required PHP extensions are installed (PDO, OpenSSL, Mbstring, Tokenizer, JSON, cURL)
3. THE Installer SHALL check that storage directory is writable
4. THE Installer SHALL check that bootstrap/cache directory is writable
5. WHEN any requirement check fails, THEN THE Installer SHALL display an error message with remediation instructions
6. WHEN all requirement checks pass, THEN THE Installer SHALL enable the "Continue" button
7. THE Installer SHALL display each requirement with a pass/fail indicator

### Requirement 5: Database Configuration and Testing

**User Story:** As a system administrator, I want to configure database connection details and test the connection, so that I can ensure the application can connect to the database before proceeding.

#### Acceptance Criteria

1. THE Installer SHALL provide input fields for database host, name, username, and password
2. WHEN the user submits database credentials, THEN THE Installer SHALL test the database connection
3. IF the database connection fails, THEN THE Installer SHALL display an error message with connection details
4. WHEN the database connection succeeds, THEN THE Installer SHALL write the configuration to the .env file
5. WHEN database configuration is saved, THEN THE Installer SHALL run database migrations
6. THE Installer SHALL display migration progress and results
7. IF migrations fail, THEN THE Installer SHALL display error details and prevent progression

### Requirement 6: Envato License Verification

**User Story:** As a product author, I want to verify Envato purchase codes during installation, so that only legitimate buyers can install the application.

#### Acceptance Criteria

1. THE Installer SHALL provide an input field for Envato purchase code
2. WHEN the user submits a purchase code, THEN THE License_Service SHALL send the code to the License_Verification_API
3. THE License_Service SHALL include application identifier and domain in the verification request
4. WHEN the License_Verification_API responds with valid status, THEN THE Installer SHALL proceed to the next step
5. IF the License_Verification_API responds with invalid status, THEN THE Installer SHALL display an error message
6. THE Installer SHALL store a hashed license reference in the Settings_Table
7. THE Installer SHALL NOT store the Envato API token in the Host_Application

### Requirement 7: Admin Account Creation

**User Story:** As an Envato buyer, I want to create an admin account during installation, so that I can access the application after installation is complete.

#### Acceptance Criteria

1. THE Installer SHALL provide input fields for admin name, email, and password
2. WHEN the user submits admin account details, THEN THE Installer SHALL validate the email format
3. WHEN the user submits admin account details, THEN THE Installer SHALL validate the password meets minimum requirements (8 characters)
4. WHEN validation passes, THEN THE Installer SHALL create a user record in the database
5. WHEN the user is created, THEN THE Installer SHALL assign the "admin" role using Spatie Permission
6. IF user creation fails, THEN THE Installer SHALL display an error message with details

### Requirement 8: Installation Finalization

**User Story:** As a system administrator, I want the installer to finalize the installation process, so that the application is ready for use.

#### Acceptance Criteria

1. WHEN all installation steps are complete, THEN THE Installer SHALL set app_installed to "true" in the Settings_Table
2. WHEN installation is finalized, THEN THE Installer SHALL clear application cache
3. WHEN installation is finalized, THEN THE Installer SHALL clear configuration cache
4. WHEN installation is finalized, THEN THE Installer SHALL redirect to the application dashboard
5. THE Installer SHALL display a success message on the finalization page

### Requirement 9: Installer UI and User Experience

**User Story:** As an Envato buyer, I want a professional and intuitive installer interface, so that I can complete installation with confidence.

#### Acceptance Criteria

1. THE Installer SHALL use Blade templates for all views
2. THE Installer SHALL use TailwindCSS for styling
3. THE Installer SHALL display a progress indicator showing completed and current steps
4. THE Installer SHALL provide clear error messages with actionable guidance
5. THE Installer SHALL provide "Back" and "Continue" buttons for navigation between steps
6. WHEN an error occurs, THEN THE Installer SHALL display the error without losing user input
7. THE Installer SHALL be responsive and work on mobile devices

### Requirement 10: Recovery and Support Access

**User Story:** As a product author, I want to provide a recovery mechanism for resetting installation state, so that I can help customers recover from installation issues.

#### Acceptance Criteria

1. THE Installer SHALL provide an artisan command "installer:unlock"
2. WHEN the installer:unlock command is executed, THEN THE Installer SHALL set app_installed to "false" in the Settings_Table
3. WHEN the installer:unlock command is executed, THEN THE Installer SHALL display a confirmation message
4. THE installer:unlock command SHALL be accessible via CLI only
5. THE installer:unlock command SHALL prompt for confirmation before resetting installation state

### Requirement 11: Package Configuration and Customization

**User Story:** As a product author, I want to customize installer settings for different products, so that I can reuse the package across multiple applications.

#### Acceptance Criteria

1. THE Installer SHALL provide a publishable configuration file
2. THE configuration file SHALL include settings for product name, version, and description
3. THE configuration file SHALL include settings for required PHP version and extensions
4. THE configuration file SHALL include settings for License_Verification_API endpoint
5. THE configuration file SHALL include settings for redirect routes after installation
6. WHEN the configuration file is published, THEN THE Installer SHALL use the published values
7. IF the configuration file is not published, THEN THE Installer SHALL use default values

### Requirement 12: Service Provider Auto-Registration

**User Story:** As a product author, I want the installer to auto-register with Laravel, so that I don't need to manually configure the package.

#### Acceptance Criteria

1. THE Installer SHALL provide a service provider that auto-discovers in Laravel 12
2. WHEN the package is installed, THEN THE service provider SHALL register installer routes
3. WHEN the package is installed, THEN THE service provider SHALL register middleware aliases
4. WHEN the package is installed, THEN THE service provider SHALL load installer views
5. WHEN the package is installed, THEN THE service provider SHALL publish configuration and migration files
6. THE service provider SHALL NOT require manual registration in the application bootstrap

### Requirement 13: Security and Data Protection

**User Story:** As a product author, I want the installer to follow security best practices, so that customer data and credentials are protected.

#### Acceptance Criteria

1. THE Installer SHALL hash admin passwords using Laravel's Hash facade
2. THE Installer SHALL validate and sanitize all user inputs
3. THE Installer SHALL use CSRF protection on all forms
4. THE Installer SHALL NOT log sensitive information (passwords, API tokens)
5. THE Installer SHALL use HTTPS for License_Verification_API requests
6. THE Installer SHALL NOT expose database credentials in error messages
7. WHEN writing to .env file, THEN THE Installer SHALL preserve existing values for non-database keys

### Requirement 14: Error Handling and Logging

**User Story:** As a system administrator, I want clear error messages when installation fails, so that I can troubleshoot and resolve issues.

#### Acceptance Criteria

1. WHEN a database connection fails, THEN THE Installer SHALL display the connection error message
2. WHEN a migration fails, THEN THE Installer SHALL display the migration error and SQL statement
3. WHEN license verification fails, THEN THE Installer SHALL display the reason (invalid code, network error, etc.)
4. WHEN file write operations fail, THEN THE Installer SHALL display permission error details
5. THE Installer SHALL log all errors to Laravel's log system
6. THE Installer SHALL NOT display stack traces to end users in production mode

### Requirement 15: Installer Routes and Controllers

**User Story:** As a developer, I want the installer to provide well-organized routes and controllers, so that the code is maintainable and extensible.

#### Acceptance Criteria

1. THE Installer SHALL define all routes in a dedicated routes file
2. THE Installer SHALL use a route prefix "install" for all installer routes
3. THE Installer SHALL provide a controller for each installation step
4. THE Installer SHALL use named routes for all installer pages
5. THE Installer SHALL apply RedirectIfInstalled_Middleware to all installer routes
6. THE Installer SHALL use RESTful conventions for route naming (index, store, etc.)
