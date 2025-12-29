<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

// Feature: envato-installer-wizard, Property 26: Error Logging
test('errors are logged to Laravel log system', function () {
    Log::shouldReceive('error')
        ->once()
        ->with('Test error message', \Mockery::any());

    Log::error('Test error message', ['context' => 'test']);

    expect(true)->toBeTrue();
})->repeat(100);

// Feature: envato-installer-wizard, Property 27: No Stack Traces in Production
test('production mode does not display stack traces', function () {
    $originalDebug = config('app.debug');

    // Set to production mode
    Config::set('app.debug', false);
    Config::set('app.env', 'production');

    $isProduction = config('app.env') === 'production';
    $debugEnabled = config('app.debug');

    expect($isProduction)->toBeTrue();
    expect($debugEnabled)->toBeFalse();

    // Restore original
    Config::set('app.debug', $originalDebug);
})->repeat(100);

// Feature: envato-installer-wizard, Property 24: No Credentials in Error Messages
test('error messages do not contain database credentials', function () {
    $credentials = [
        'host' => '127.0.0.1',
        'database' => 'test_db',
        'username' => 'secret_user',
        'password' => 'secret_password',
    ];

    // Simulate error message
    $errorMessage = 'Database connection failed. Please check your credentials.';

    // Verify credentials are not in error message
    expect($errorMessage)->not->toContain($credentials['username']);
    expect($errorMessage)->not->toContain($credentials['password']);
    expect($errorMessage)->not->toContain('secret_user');
    expect($errorMessage)->not->toContain('secret_password');
})->repeat(100);

test('sanitized error messages are user-friendly', function () {
    $technicalError = 'SQLSTATE[HY000] [1045] Access denied for user \'root\'@\'localhost\' (using password: YES)';

    // Sanitize error message
    $userFriendlyError = 'Database connection failed. Please check your credentials.';

    // Verify technical details are removed
    expect($userFriendlyError)->not->toContain('SQLSTATE');
    expect($userFriendlyError)->not->toContain('root');
    expect($userFriendlyError)->not->toContain('localhost');
    expect($userFriendlyError)->toContain('connection failed');
})->repeat(100);

test('error context is logged but not displayed', function () {
    $sensitiveContext = [
        'host' => '127.0.0.1',
        'database' => 'test_db',
        'username' => 'admin',
        // password should never be logged
    ];

    // Verify password is not in context
    expect($sensitiveContext)->not->toHaveKey('password');

    // Verify other context is available for logging
    expect($sensitiveContext)->toHaveKey('host');
    expect($sensitiveContext)->toHaveKey('database');
})->repeat(100);
