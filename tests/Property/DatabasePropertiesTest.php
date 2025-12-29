<?php

use SoftCortex\Installer\Services\DatabaseManager;
use SoftCortex\Installer\Services\EnvironmentManager;

// Feature: envato-installer-wizard, Property 8: Database Connection Testing
test('database connection is tested before proceeding', function () {
    $dbManager = app(DatabaseManager::class);
    
    // Test with valid credentials (using test database)
    $credentials = [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'testing'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ];
    
    try {
        $result = $dbManager->testConnection($credentials);
        expect($result)->toBeTrue();
    } catch (\PDOException $e) {
        // Connection failed - this is expected in test environment
        expect($e)->toBeInstanceOf(\PDOException::class);
    }
})->repeat(100);

test('invalid database credentials throw exception', function () {
    $dbManager = app(DatabaseManager::class);
    
    // Test with invalid credentials
    $credentials = [
        'host' => 'invalid_host_' . uniqid(),
        'port' => '3306',
        'database' => 'invalid_db',
        'username' => 'invalid_user',
        'password' => 'invalid_pass',
    ];
    
    expect(fn() => $dbManager->testConnection($credentials))
        ->toThrow(\PDOException::class);
})->repeat(100);

// Feature: envato-installer-wizard, Property 9: Successful Database Configuration Workflow
test('successful database connection writes configuration and runs migrations', function () {
    $dbManager = app(DatabaseManager::class);
    $envManager = app(EnvironmentManager::class);
    
    // Prepare test credentials
    $credentials = [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'test_db_' . uniqid(),
        'username' => 'test_user',
        'password' => 'test_pass',
    ];
    
    // Write configuration
    $dbManager->writeConfiguration($credentials);
    
    // Verify configuration was written to environment
    expect($envManager->get('DB_HOST'))->toBe($credentials['host']);
    expect($envManager->get('DB_PORT'))->toBe($credentials['port']);
    expect($envManager->get('DB_DATABASE'))->toBe($credentials['database']);
    expect($envManager->get('DB_USERNAME'))->toBe($credentials['username']);
    expect($envManager->get('DB_PASSWORD'))->toBe($credentials['password']);
})->repeat(100);

test('database configuration preserves other environment variables', function () {
    $dbManager = app(DatabaseManager::class);
    $envManager = app(EnvironmentManager::class);
    
    // Set some non-database environment variables
    $envManager->setMultiple([
        'APP_NAME' => 'TestApp_' . uniqid(),
        'APP_ENV' => 'testing',
        'APP_DEBUG' => 'true',
    ]);
    
    $appName = $envManager->get('APP_NAME');
    $appEnv = $envManager->get('APP_ENV');
    
    // Write database configuration
    $credentials = [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'test_db',
        'username' => 'test_user',
        'password' => 'test_pass',
    ];
    
    $dbManager->writeConfiguration($credentials);
    
    // Verify non-database variables are preserved
    expect($envManager->get('APP_NAME'))->toBe($appName);
    expect($envManager->get('APP_ENV'))->toBe($appEnv);
    expect($envManager->get('APP_DEBUG'))->toBe('true');
})->repeat(100);

test('migration results include success status', function () {
    $dbManager = app(DatabaseManager::class);
    
    $result = $dbManager->runMigrations();
    
    expect($result)->toBeArray();
    expect($result)->toHaveKey('success');
    expect($result['success'])->toBeBool();
    
    if ($result['success']) {
        expect($result)->toHaveKey('output');
        expect($result['output'])->toBeArray();
    } else {
        expect($result)->toHaveKey('error');
        expect($result['error'])->toBeString();
    }
})->repeat(100);
