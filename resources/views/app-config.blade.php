@extends('installer::layouts.master')

@section('title', 'Application Configuration - Installation Wizard')

@section('content')
    <div>
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Application Configuration</h2>
        <p class="text-gray-600 mb-6">Configure your application's basic settings</p>

        <form method="POST" action="{{ route('installer.app-config.store') }}" class="space-y-6">
            @csrf

            <!-- App Name -->
            <div>
                <label for="app_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Application Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="app_name"
                    name="app_name"
                    value="{{ old('app_name', $currentConfig['app_name']) }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('app_name') border-red-500 @enderror"
                    placeholder="My Application"
                >
                @error('app_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">The name of your application</p>
            </div>

            <!-- App Environment -->
            <div>
                <label for="app_env" class="block text-sm font-medium text-gray-700 mb-2">
                    Environment <span class="text-red-500">*</span>
                </label>
                <select
                    id="app_env"
                    name="app_env"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('app_env') border-red-500 @enderror"
                >
                    <option value="local" {{ old('app_env', $currentConfig['app_env']) == 'local' ? 'selected' : '' }}>Local</option>
                    <option value="development" {{ old('app_env', $currentConfig['app_env']) == 'development' ? 'selected' : '' }}>Development</option>
                    <option value="staging" {{ old('app_env', $currentConfig['app_env']) == 'staging' ? 'selected' : '' }}>Staging</option>
                    <option value="production" {{ old('app_env', $currentConfig['app_env']) == 'production' ? 'selected' : '' }}>Production</option>
                </select>
                @error('app_env')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">The environment your application is running in</p>
            </div>

            <!-- App Debug -->
            <div>
                <label class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input
                            type="checkbox"
                            id="app_debug"
                            name="app_debug"
                            value="1"
                            {{ old('app_debug', $currentConfig['app_debug']) == 'true' ? 'checked' : '' }}
                            class="sr-only peer"
                        >
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <span class="ms-3 text-sm font-medium text-gray-700">
                        Enable Debug Mode
                    </span>
                </label>
                @error('app_debug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Show detailed error messages (disable in production)</p>
            </div>

            <!-- App URL -->
            <div>
                <label for="app_url" class="block text-sm font-medium text-gray-700 mb-2">
                    Application URL <span class="text-red-500">*</span>
                </label>
                <input
                    type="url"
                    id="app_url"
                    name="app_url"
                    value="{{ old('app_url', $currentConfig['app_url']) }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('app_url') border-red-500 @enderror"
                    placeholder="https://example.com"
                >
                @error('app_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">The URL where your application will be accessible</p>
            </div>

            <!-- App Timezone -->
            <div>
                <label for="app_timezone" class="block text-sm font-medium text-gray-700 mb-2">
                    Timezone <span class="text-red-500">*</span>
                </label>
                <select
                    id="app_timezone"
                    name="app_timezone"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('app_timezone') border-red-500 @enderror"
                >
                    @foreach($timezones as $value => $label)
                        <option value="{{ $value }}" {{ old('app_timezone', $currentConfig['app_timezone']) == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('app_timezone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">The default timezone for your application</p>
            </div>

            <!-- App Locale -->
            <div>
                <label for="app_locale" class="block text-sm font-medium text-gray-700 mb-2">
                    Language <span class="text-red-500">*</span>
                </label>
                <select
                    id="app_locale"
                    name="app_locale"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('app_locale') border-red-500 @enderror"
                >
                    @foreach($availableLocales as $value => $label)
                        <option value="{{ $value }}" {{ old('app_locale', $currentConfig['app_locale']) == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('app_locale')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">The default language for your application</p>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between pt-6 border-t">
                <a
                    href="{{ route('installer.welcome') }}"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg transition duration-200"
                >
                    Back
                </a>
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200"
                >
                    Continue
                </button>
            </div>
        </form>
    </div>
@endsection

@php
    $currentStep = 2;
@endphp
