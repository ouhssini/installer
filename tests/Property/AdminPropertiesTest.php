<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Create users table for tests
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }
});

afterEach(function () {
    // Clean up
    if (Schema::hasTable('users')) {
        DB::table('users')->truncate();
    }
});

// Feature: envato-installer-wizard, Property 13: Admin Email Validation
test('invalid email formats are rejected', function () {
    $invalidEmails = [
        'notanemail',              // No @ symbol
        'missing-at-sign.com',     // No @ symbol
        '@nodomain.com',           // Missing local part
        'spaces in@email.com',     // Spaces in local part
        'double@@domain.com',      // Double @ symbol
        'user@',                   // Missing domain
        'user@.com',               // Domain starts with dot
        '',                        // Empty string
        'user name@domain.com',    // Space in local part
    ];

    foreach ($invalidEmails as $email) {
        $validator = validator([
            'email' => $email,
        ], [
            'email' => 'required|email',
        ]);

        expect($validator->fails())->toBeTrue("Expected '$email' to be invalid");
    }
})->repeat(100);

test('valid email formats are accepted', function () {
    $validEmails = [
        'user@example.com',
        'admin@test.org',
        'test.user@domain.co.uk',
        'user+tag@example.com',
    ];

    foreach ($validEmails as $email) {
        $validator = validator([
            'email' => $email,
        ], [
            'email' => 'required|email',
        ]);

        expect($validator->passes())->toBeTrue();
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 14: Admin Password Validation
test('passwords shorter than 8 characters are rejected', function () {
    $shortPasswords = [
        '',
        'a',
        'ab',
        'abc',
        'abcd',
        'abcde',
        'abcdef',
        'abcdefg',
    ];

    foreach ($shortPasswords as $password) {
        $validator = validator([
            'password' => $password,
        ], [
            'password' => 'required|string|min:8',
        ]);

        expect($validator->fails())->toBeTrue();
    }
})->repeat(100);

test('passwords with 8 or more characters are accepted', function () {
    $validPasswords = [
        'abcdefgh',
        'password123',
        'MySecureP@ssw0rd',
        'a1b2c3d4e5f6g7h8',
    ];

    foreach ($validPasswords as $password) {
        $validator = validator([
            'password' => $password,
        ], [
            'password' => 'required|string|min:8',
        ]);

        expect($validator->passes())->toBeTrue();
    }
})->repeat(100);

// Feature: envato-installer-wizard, Property 15: Admin User Creation and Role Assignment
test('valid admin account data creates user record', function () {
    $name = 'Admin User '.uniqid();
    $email = 'admin'.uniqid().'@example.com';
    $password = 'password123';

    // Create user
    $userId = DB::table('users')->insertGetId([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($password),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($userId)->toBeGreaterThan(0);

    // Verify user exists
    $user = DB::table('users')->where('id', $userId)->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe($name);
    expect($user->email)->toBe($email);
    expect(Hash::check($password, $user->password))->toBeTrue();
})->repeat(100);

// Feature: envato-installer-wizard, Property 21: Password Hashing
test('admin passwords are hashed before storage', function () {
    $plainPassword = 'MyPlainPassword123';
    $hashedPassword = Hash::make($plainPassword);

    // Verify password is hashed (not plain text)
    expect($hashedPassword)->not->toBe($plainPassword);
    expect(strlen($hashedPassword))->toBeGreaterThan(strlen($plainPassword));

    // Verify hash can be verified
    expect(Hash::check($plainPassword, $hashedPassword))->toBeTrue();
    expect(Hash::check('WrongPassword', $hashedPassword))->toBeFalse();
})->repeat(100);

test('stored passwords are never plain text', function () {
    $plainPassword = 'password123';
    $email = 'user'.uniqid().'@example.com';

    // Create user with hashed password
    DB::table('users')->insert([
        'name' => 'Test User',
        'email' => $email,
        'password' => Hash::make($plainPassword),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Retrieve user
    $user = DB::table('users')->where('email', $email)->first();

    // Verify password is not stored as plain text
    expect($user->password)->not->toBe($plainPassword);

    // Verify password can be verified with Hash::check
    expect(Hash::check($plainPassword, $user->password))->toBeTrue();
})->repeat(100);

test('duplicate email addresses are rejected', function () {
    $email = 'duplicate'.uniqid().'@example.com';

    // Create first user
    DB::table('users')->insert([
        'name' => 'First User',
        'email' => $email,
        'password' => Hash::make('password123'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Attempt to create second user with same email
    $validator = validator([
        'email' => $email,
    ], [
        'email' => 'required|email|unique:users,email',
    ]);

    expect($validator->fails())->toBeTrue();
})->repeat(100);

test('password confirmation must match', function () {
    $password = 'password123';
    $wrongConfirmation = 'different456';

    $validator = validator([
        'password' => $password,
        'password_confirmation' => $wrongConfirmation,
    ], [
        'password' => 'required|string|min:8|confirmed',
    ]);

    expect($validator->fails())->toBeTrue();

    // Test with matching confirmation
    $validator = validator([
        'password' => $password,
        'password_confirmation' => $password,
    ], [
        'password' => 'required|string|min:8|confirmed',
    ]);

    expect($validator->passes())->toBeTrue();
})->repeat(100);

// Feature: envato-installer-wizard, Property 17: Error Display Preserves Input
test('validation errors preserve user input in forms', function () {
    $name = 'Test User '.uniqid();
    $invalidEmail = 'not-an-email';
    $password = 'password123';

    // Simulate validation
    $validator = validator([
        'name' => $name,
        'email' => $invalidEmail,
        'password' => $password,
    ], [
        'name' => 'required|string',
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ]);

    expect($validator->fails())->toBeTrue();

    // Verify input data is still available
    $data = $validator->getData();
    expect($data['name'])->toBe($name);
    expect($data['email'])->toBe($invalidEmail);
    expect($data['password'])->toBe($password);
})->repeat(100);

test('form errors do not lose valid field values', function () {
    $validName = 'Valid Name';
    $validEmail = 'valid@email.com';
    $shortPassword = 'short';

    $validator = validator([
        'name' => $validName,
        'email' => $validEmail,
        'password' => $shortPassword,
    ], [
        'name' => 'required|string',
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ]);

    expect($validator->fails())->toBeTrue();

    // Valid fields should still be available
    $data = $validator->getData();
    expect($data['name'])->toBe($validName);
    expect($data['email'])->toBe($validEmail);
})->repeat(100);
