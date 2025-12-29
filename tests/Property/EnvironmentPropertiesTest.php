<?php

use SoftCortex\Installer\Services\EnvironmentManager;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create a test .env file
    $this->testEnvPath = base_path('.env.test');
    $this->originalEnvPath = base_path('.env');
    
    // Backup original .env if it exists
    if (File::exists($this->originalEnvPath)) {
        File::copy($this->originalEnvPath, $this->originalEnvPath . '.original');
    }
});

afterEach(function () {
    // Restore original .env (Windows-compatible approach)
    if (File::exists($this->originalEnvPath . '.original')) {
        // Delete current .env first to avoid Windows file locking issues
        if (File::exists($this->originalEnvPath)) {
            File::delete($this->originalEnvPath);
        }
        // Copy back the original
        File::copy($this->originalEnvPath . '.original', $this->originalEnvPath);
        // Clean up the backup
        File::delete($this->originalEnvPath . '.original');
    }
    
    // Clean up test files
    if (File::exists($this->originalEnvPath . '.backup')) {
        File::delete($this->originalEnvPath . '.backup');
    }
});

// Feature: envato-installer-wizard, Property 25: Environment File Preservation
test('environment file updates preserve existing keys that are not being updated', function () {
    $envManager = app(EnvironmentManager::class);
    
    // Create initial .env content with multiple keys
    $initialContent = <<<ENV
APP_NAME=TestApp
APP_ENV=local
APP_KEY=base64:test123
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
ENV;
    
    File::put(base_path('.env'), $initialContent);
    
    // Update only database keys
    $envManager->setMultiple([
        'DB_HOST' => '192.168.1.100',
        'DB_PORT' => '3307',
    ]);
    
    // Verify updated keys
    expect($envManager->get('DB_HOST'))->toBe('192.168.1.100');
    expect($envManager->get('DB_PORT'))->toBe('3307');
    
    // Verify preserved keys
    expect($envManager->get('APP_NAME'))->toBe('TestApp');
    expect($envManager->get('APP_ENV'))->toBe('local');
    expect($envManager->get('APP_KEY'))->toBe('base64:test123');
    expect($envManager->get('APP_DEBUG'))->toBe('true');
    expect($envManager->get('APP_URL'))->toBe('http://localhost');
    expect($envManager->get('DB_CONNECTION'))->toBe('mysql');
})->repeat(100);

test('setting new keys does not affect existing keys', function () {
    $envManager = app(EnvironmentManager::class);
    
    // Create initial .env content
    $initialContent = <<<ENV
EXISTING_KEY_1=value1
EXISTING_KEY_2=value2
EXISTING_KEY_3=value3
ENV;
    
    File::put(base_path('.env'), $initialContent);
    
    // Add new keys
    $newKey = 'NEW_KEY_' . uniqid();
    $newValue = 'new_value_' . uniqid();
    
    $envManager->set($newKey, $newValue);
    
    // Verify new key exists
    expect($envManager->get($newKey))->toBe($newValue);
    
    // Verify existing keys are preserved
    expect($envManager->get('EXISTING_KEY_1'))->toBe('value1');
    expect($envManager->get('EXISTING_KEY_2'))->toBe('value2');
    expect($envManager->get('EXISTING_KEY_3'))->toBe('value3');
})->repeat(100);

test('updating multiple keys preserves all other keys', function () {
    $envManager = app(EnvironmentManager::class);
    
    // Create initial .env with 10 keys
    $keys = [];
    $content = [];
    for ($i = 1; $i <= 10; $i++) {
        $key = "KEY_{$i}";
        $value = "value_{$i}";
        $keys[$key] = $value;
        $content[] = "{$key}={$value}";
    }
    
    File::put(base_path('.env'), implode("\n", $content));
    
    // Update 3 random keys
    $keysToUpdate = array_rand($keys, 3);
    $updates = [];
    foreach ($keysToUpdate as $keyName) {
        // $keyName is already the full key name like "KEY_1", "KEY_2", etc.
        $updates[$keyName] = "updated_value_" . uniqid();
    }
    
    $envManager->setMultiple($updates);
    
    // Verify updated keys have new values
    foreach ($updates as $key => $value) {
        expect($envManager->get($key))->toBe($value);
    }
    
    // Verify non-updated keys are preserved
    foreach ($keys as $key => $originalValue) {
        if (!isset($updates[$key])) {
            expect($envManager->get($key))->toBe($originalValue);
        }
    }
})->repeat(100);

test('comments and empty lines are preserved', function () {
    $envManager = app(EnvironmentManager::class);
    
    $initialContent = <<<ENV
# Application Configuration
APP_NAME=TestApp

# Database Configuration
DB_HOST=localhost
DB_PORT=3306

# Cache Configuration
CACHE_DRIVER=file
ENV;
    
    File::put(base_path('.env'), $initialContent);
    
    // Update one key
    $envManager->set('DB_HOST', '127.0.0.1');
    
    // Read the file and verify comments are still there
    $content = File::get(base_path('.env'));
    expect($content)->toContain('# Application Configuration');
    expect($content)->toContain('# Database Configuration');
    expect($content)->toContain('# Cache Configuration');
    
    // Verify the update worked
    expect($envManager->get('DB_HOST'))->toBe('127.0.0.1');
})->repeat(100);
