<?php

use Illuminate\Support\Facades\View;

test('welcome view exists and contains required elements', function () {
    expect(View::exists('installer::welcome'))->toBeTrue();

    // Share errors with view
    View::share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));

    $view = view('installer::welcome', [
        'product' => [
            'name' => 'Test App',
            'version' => '1.0.0',
            'description' => 'Test description',
        ],
    ]);

    $content = $view->render();

    expect($content)->toContain('Test App');
    expect($content)->toContain('1.0.0');
    expect($content)->toContain('Get Started');
});

test('requirements view exists and displays requirements', function () {
    expect(View::exists('installer::requirements'))->toBeTrue();

    // Share errors with view
    View::share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));

    $view = view('installer::requirements', [
        'requirements' => [
            'php' => ['required' => '8.2', 'current' => '8.4', 'satisfied' => true],
            'extensions' => ['pdo' => ['satisfied' => true]],
            'directories' => ['storage' => ['satisfied' => true]],
            'all_satisfied' => true,
        ],
    ]);

    $content = $view->render();

    expect($content)->toContain('Server Requirements');
    expect($content)->toContain('PHP Version');
});

test('database view exists and contains form fields', function () {
    expect(View::exists('installer::database'))->toBeTrue();

    // Share errors with view
    View::share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));

    $view = view('installer::database');
    $content = $view->render();

    expect($content)->toContain('Database Configuration');
    expect($content)->toContain('name="host"');
    expect($content)->toContain('name="port"');
    expect($content)->toContain('name="database"');
    expect($content)->toContain('name="username"');
    expect($content)->toContain('name="password"');
});

test('license view exists and contains purchase code field', function () {
    expect(View::exists('installer::license'))->toBeTrue();

    // Share errors with view
    View::share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));

    $view = view('installer::license', ['licenseEnabled' => true]);
    $content = $view->render();

    expect($content)->toContain('License Verification');
    expect($content)->toContain('name="purchase_code"');
});

test('admin view exists and contains all required fields', function () {
    expect(View::exists('installer::admin'))->toBeTrue();

    // Share errors with view
    View::share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));

    $view = view('installer::admin');
    $content = $view->render();

    expect($content)->toContain('Create Admin Account');
    expect($content)->toContain('name="name"');
    expect($content)->toContain('name="email"');
    expect($content)->toContain('name="password"');
    expect($content)->toContain('name="password_confirmation"');
});

test('finalize view exists and shows success message', function () {
    expect(View::exists('installer::finalize'))->toBeTrue();

    // Share errors with view
    View::share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));

    $view = view('installer::finalize');
    $content = $view->render();

    expect($content)->toContain('Installation Complete');
    expect($content)->toContain('Go to Dashboard');
});

test('all views include CSRF token', function () {
    // Share errors with view
    View::share('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag));

    $views = ['welcome', 'requirements', 'database', 'license', 'admin', 'finalize'];

    foreach ($views as $viewName) {
        $view = view('installer::'.$viewName, [
            'requirements' => ['php' => ['required' => '8.2', 'current' => '8.4', 'satisfied' => true], 'extensions' => [], 'directories' => [], 'all_satisfied' => true],
            'product' => ['name' => 'Test', 'version' => '1.0.0'],
            'licenseEnabled' => true,
        ]);

        $content = $view->render();
        expect($content)->toContain('csrf');
    }
});
