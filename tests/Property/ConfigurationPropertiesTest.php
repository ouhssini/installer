<?php

use Illuminate\Support\Facades\Config;

// Feature: envato-installer-wizard, Property 19: Configuration Override
test('published config values override default values', function () {
    // Set custom config values
    Config::set('installer.product.name', 'Custom App Name');
    Config::set('installer.product.version', '2.0.0');
    
    // Verify custom values are used
    expect(config('installer.product.name'))->toBe('Custom App Name');
    expect(config('installer.product.version'))->toBe('2.0.0');
})->repeat(100);

test('unpublished config keys use default values', function () {
    // Test with a key that doesn't exist in the config
    $name = config('installer.nonexistent_key', 'Default App');
    expect($name)->toBe('Default App');
})->repeat(100);

test('config structure contains all required keys', function () {
    $config = config('installer');
    
    expect($config)->toBeArray();
    expect($config)->toHaveKey('product');
    expect($config)->toHaveKey('requirements');
    expect($config)->toHaveKey('license');
    expect($config)->toHaveKey('routes');
    expect($config)->toHaveKey('admin');
})->repeat(100);

test('product config contains name version and description', function () {
    $product = config('installer.product');
    
    expect($product)->toHaveKey('name');
    expect($product)->toHaveKey('version');
    expect($product)->toHaveKey('description');
})->repeat(100);

test('requirements config contains php extensions and directories', function () {
    $requirements = config('installer.requirements');
    
    expect($requirements)->toHaveKey('php');
    expect($requirements)->toHaveKey('extensions');
    expect($requirements)->toHaveKey('directories');
    
    expect($requirements['extensions'])->toBeArray();
    expect($requirements['directories'])->toBeArray();
})->repeat(100);

test('license config contains Envato API settings', function () {
    $license = config('installer.license');
    
    expect($license)->toHaveKey('enabled');
    expect($license)->toHaveKey('envato_personal_token');
    expect($license)->toHaveKey('envato_item_id');
})->repeat(100);

test('routes config contains prefix and middleware', function () {
    $routes = config('installer.routes');
    
    expect($routes)->toHaveKey('prefix');
    expect($routes)->toHaveKey('middleware');
    expect($routes)->toHaveKey('redirect_after_install');
})->repeat(100);
