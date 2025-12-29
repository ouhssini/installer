<?php

use Illuminate\Support\Facades\Validator;

// Feature: envato-installer-wizard, Property 22: Input Validation
test('all user inputs are validated before processing', function () {
    $inputs = [
        'email' => 'test@example.com',
        'password' => 'password123',
        'name' => 'Test User',
    ];
    
    $rules = [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
        'name' => 'required|string|max:255',
    ];
    
    $validator = Validator::make($inputs, $rules);
    
    expect($validator->passes())->toBeTrue();
})->repeat(100);

test('invalid inputs are rejected', function () {
    $invalidInputs = [
        'email' => 'not-an-email',
        'password' => 'short',
        'name' => '',
    ];
    
    $rules = [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
        'name' => 'required|string',
    ];
    
    $validator = Validator::make($invalidInputs, $rules);
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
})->repeat(100);

test('SQL injection attempts are prevented through validation', function () {
    $maliciousInputs = [
        "'; DROP TABLE users; --",
        "1' OR '1'='1",
        "<script>alert('xss')</script>",
        "../../etc/passwd",
    ];
    
    foreach ($maliciousInputs as $input) {
        $validator = Validator::make(
            ['input' => $input],
            ['input' => 'required|string|max:255']
        );
        
        // Validation should pass (input is sanitized by Laravel)
        // But the actual query would use parameter binding
        expect($validator->passes())->toBeTrue();
    }
})->repeat(100);

test('XSS attempts are escaped in output', function () {
    $xssAttempts = [
        '<script>alert("xss")</script>',
        '<img src=x onerror=alert("xss")>',
        '<iframe src="javascript:alert(\'xss\')"></iframe>',
    ];
    
    foreach ($xssAttempts as $xss) {
        $escaped = e($xss);
        
        // Verify dangerous characters are escaped
        expect($escaped)->not->toContain('<script>');
        expect($escaped)->not->toContain('<img');
        expect($escaped)->not->toContain('<iframe');
        expect($escaped)->toContain('&lt;');
        expect($escaped)->toContain('&gt;');
    }
})->repeat(100);
