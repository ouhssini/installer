@extends('installer::layouts.master')

@section('title', 'Installation Complete - Installation Wizard')

@section('content')
<div class="text-center">
    <div class="mb-6">
        <svg class="w-20 h-20 mx-auto text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>

    <h2 class="text-2xl font-bold text-gray-800 mb-4">Installation Complete!</h2>
    
    <p class="text-gray-700 mb-8">Your application has been successfully installed and configured.</p>

    <div class="text-left bg-gray-50 p-6 rounded-lg mb-8">
        <h3 class="font-semibold text-gray-800 mb-3">What's Next?</h3>
        <ul class="list-disc list-inside text-gray-700 space-y-2">
            <li>Click the button below to access your application</li>
            <li>Log in with the admin credentials you created</li>
            <li>Configure your application settings</li>
            <li>Start using your application!</li>
        </ul>
    </div>

    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-8">
        <p class="font-semibold">Important:</p>
        <p>For security reasons, please ensure your server is properly configured and all sensitive files are protected.</p>
    </div>

    <form method="POST" action="{{ route('installer.finalize.store') }}">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200">
            Go to Dashboard
        </button>
    </form>
</div>
@endsection

@php
    $currentStep = 7;
@endphp
