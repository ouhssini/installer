@extends('installer::layouts.master')

@section('title', 'Welcome - Installation Wizard')

@section('content')
<div class="text-center">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">Welcome to {{ $product['name'] ?? 'Application' }} Installation</h2>
    <p class="text-gray-600 mb-6">Version {{ $product['version'] ?? '1.0.0' }}</p>
    
    <div class="text-left bg-gray-50 p-6 rounded-lg mb-8">
        <p class="text-gray-700 mb-4">{{ $product['description'] ?? 'Professional Laravel application with installer wizard' }}</p>
        
        <h3 class="font-semibold text-gray-800 mb-2">Before you begin:</h3>
        <ul class="list-disc list-inside text-gray-700 space-y-2">
            <li>Ensure your server meets the minimum requirements</li>
            <li>Have your database credentials ready</li>
            <li>Have your Envato purchase code ready (if applicable)</li>
            <li>This wizard will guide you through the installation process</li>
        </ul>
    </div>

    <form method="POST" action="{{ route('installer.welcome.store') }}">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200">
            Get Started
        </button>
    </form>
</div>
@endsection

@php
    $currentStep = 1;
@endphp
