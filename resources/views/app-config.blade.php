@extends('installer::layouts.master')

@section('title', 'Application Configuration - Installation Wizard')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Application Configuration</h2>
    <p class="text-gray-600 mb-6">Configure your application's basic settings</p>

    {{-- Manual validation errors (installer-safe) --}}
    @if(!empty($validationErrors))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach($validationErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('installer.app-config.store') }}" class="space-y-6">

        <!-- App Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Application Name <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                name="app_name"
                value="{{ $currentConfig['app_name'] }}"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="My Application"
            >
            <p class="mt-1 text-sm text-gray-500">The name of your application</p>
        </div>

        <!-- App Environment -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Environment <span class="text-red-500">*</span>
            </label>
            <select
                name="app_env"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                @foreach(['local','development','staging','production'] as $env)
                    <option value="{{ $env }}" {{ $currentConfig['app_env'] === $env ? 'selected' : '' }}>
                        {{ ucfirst($env) }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-gray-500">The environment your application is running in</p>
        </div>

        <!-- App Debug -->
        <div>
            <label class="flex items-center cursor-pointer">
                <input
                    type="checkbox"
                    name="app_debug"
                    value="1"
                    {{ $currentConfig['app_debug'] === 'true' ? 'checked' : '' }}
                    class="sr-only peer"
                >
                <div class="w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-blue-600 relative">
                    <div class="absolute top-[2px] left-[2px] h-5 w-5 bg-white rounded-full transition-all peer-checked:translate-x-full"></div>
                </div>
                <span class="ms-3 text-sm font-medium text-gray-700">
                    Enable Debug Mode
                </span>
            </label>
            <p class="mt-1 text-sm text-gray-500">Show detailed error messages (disable in production)</p>
        </div>

        <!-- App URL -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Application URL <span class="text-red-500">*</span>
            </label>
            <input
                type="url"
                name="app_url"
                value="{{ $currentConfig['app_url'] }}"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="https://example.com"
            >
            <p class="mt-1 text-sm text-gray-500">The URL where your application will be accessible</p>
        </div>

        <!-- App Timezone -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Timezone <span class="text-red-500">*</span>
            </label>
            <select
                name="app_timezone"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                @foreach($timezones as $value => $label)
                    <option value="{{ $value }}" {{ $currentConfig['app_timezone'] === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- App Locale -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Language <span class="text-red-500">*</span>
            </label>
            <select
                name="app_locale"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                @foreach($availableLocales as $value => $label)
                    <option value="{{ $value }}" {{ $currentConfig['app_locale'] === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between pt-6 border-t">
            <a href="{{ route('installer.welcome') }}"
               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg">
                Back
            </a>

            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg">
                Continue
            </button>
        </div>
    </form>
</div>
@endsection

@php
    $currentStep = 2;
@endphp
