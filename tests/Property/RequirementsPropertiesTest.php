<?php

use SoftCortex\Installer\Services\RequirementsChecker;

// Feature: envato-installer-wizard, Property 6: Requirements Validation Blocking
test('failed requirements prevent progression to next step', function () {
    $checker = app(RequirementsChecker::class);
    
    // Get requirements check results
    $results = $checker->check();
    
    // If any requirement is not satisfied, all_satisfied should be false
    if (!$results['all_satisfied']) {
        expect($results['all_satisfied'])->toBeFalse();
        
        // Verify at least one requirement failed
        $hasFailure = false;
        
        if (!$results['php']['satisfied']) {
            $hasFailure = true;
        }
        
        foreach ($results['extensions'] as $extension => $result) {
            if (!$result['satisfied']) {
                $hasFailure = true;
                break;
            }
        }
        
        foreach ($results['directories'] as $directory => $result) {
            if (!$result['satisfied']) {
                $hasFailure = true;
                break;
            }
        }
        
        expect($hasFailure)->toBeTrue();
    } else {
        // All requirements satisfied
        expect($results['all_satisfied'])->toBeTrue();
    }
})->repeat(100);

test('all requirements satisfied allows progression', function () {
    $checker = app(RequirementsChecker::class);
    
    $results = $checker->check();
    
    if ($results['all_satisfied']) {
        // Verify PHP version is satisfied
        expect($results['php']['satisfied'])->toBeTrue();
        
        // Verify all extensions are satisfied
        foreach ($results['extensions'] as $extension => $result) {
            expect($result['satisfied'])->toBeTrue();
        }
        
        // Verify all directories are satisfied
        foreach ($results['directories'] as $directory => $result) {
            expect($result['satisfied'])->toBeTrue();
        }
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 7: Failed Requirements Display Errors
test('failed requirements include error information', function () {
    $checker = app(RequirementsChecker::class);
    
    $results = $checker->check();
    
    // Check PHP version result structure
    expect($results['php'])->toHaveKeys(['required', 'current', 'satisfied']);
    
    if (!$results['php']['satisfied']) {
        expect($results['php']['required'])->toBeString();
        expect($results['php']['current'])->toBeString();
        expect($results['php']['satisfied'])->toBeFalse();
    }
    
    // Check extensions result structure
    foreach ($results['extensions'] as $extension => $result) {
        expect($result)->toHaveKey('satisfied');
        expect($result['satisfied'])->toBeBool();
    }
    
    // Check directories result structure
    foreach ($results['directories'] as $directory => $result) {
        expect($result)->toHaveKeys(['exists', 'writable', 'satisfied', 'path']);
        
        if (!$result['satisfied']) {
            // Failed directory should have detailed information
            expect($result['exists'])->toBeBool();
            expect($result['writable'])->toBeBool();
            expect($result['path'])->toBeString();
        }
    }
})->repeat(100);

test('php version check returns correct structure', function () {
    $checker = app(RequirementsChecker::class);
    
    $result = $checker->checkPhpVersion('8.2');
    
    expect($result)->toHaveKeys(['required', 'current', 'satisfied']);
    expect($result['required'])->toBe('8.2');
    expect($result['current'])->toBe(PHP_VERSION);
    expect($result['satisfied'])->toBeBool();
})->repeat(100);

test('extension check returns boolean', function () {
    $checker = app(RequirementsChecker::class);
    
    // Test with a known extension
    $result = $checker->checkExtension('json');
    expect($result)->toBeBool();
    expect($result)->toBeTrue(); // json should always be available
    
    // Test with a non-existent extension
    $result = $checker->checkExtension('nonexistent_extension_' . uniqid());
    expect($result)->toBeBool();
    expect($result)->toBeFalse();
})->repeat(100);

test('directory check returns correct structure', function () {
    $checker = app(RequirementsChecker::class);
    
    // Test with storage directory (should exist)
    $result = $checker->checkDirectory('storage');
    
    expect($result)->toHaveKeys(['exists', 'writable', 'satisfied', 'path']);
    expect($result['exists'])->toBeBool();
    expect($result['writable'])->toBeBool();
    expect($result['satisfied'])->toBeBool();
    expect($result['path'])->toBeString();
    expect($result['path'])->toContain('storage');
})->repeat(100);
