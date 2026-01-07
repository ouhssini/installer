@extends('installer::layouts.master')

@section('title', 'Server Requirements - Installation Wizard')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Server Requirements</h2>

    <div class="space-y-6">
        <!-- PHP Version -->
        <div class="border rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-3">PHP Version</h3>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-gray-700">Required: PHP {{ $requirements['php']['required'] }}+</span>
                    <span class="text-gray-600 ml-2">(Current: {{ $requirements['php']['current'] }})</span>
                </div>
                <div>
                    @if($requirements['php']['satisfied'])
                        <span class="text-green-600 font-semibold">✓ Pass</span>
                    @else
                        <span class="text-red-600 font-semibold">✗ Fail</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- PHP Extensions -->
        <div class="border rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-3">PHP Extensions</h3>
            <div class="space-y-2">
                @foreach($requirements['extensions'] as $extension => $result)
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">{{ ucfirst($extension) }}</span>
                    <div>
                        @if($result['satisfied'])
                            <span class="text-green-600 font-semibold">✓ Installed</span>
                        @else
                            <span class="text-red-600 font-semibold">✗ Missing</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Directory Permissions -->
        <div class="border rounded-lg p-4">
            <h3 class="font-semibold text-gray-800 mb-3">Directory Permissions</h3>
            <div class="space-y-2">
                @foreach($requirements['directories'] as $directory => $result)
                <div class="flex items-center justify-between">
                    <span class="text-gray-700">{{ $directory }}</span>
                    <div>
                        @if($result['satisfied'])
                            <span class="text-green-600 font-semibold">✓ Writable</span>
                        @else
                            <span class="text-red-600 font-semibold">✗ Not Writable</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @if(!$requirements['all_satisfied'])
    <div class="mt-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded">
        <p class="font-semibold">Action Required:</p>
        <p>Please resolve the failed requirements before continuing with the installation.</p>
    </div>
    @endif

    <form method="POST" action="{{ route('installer.requirements.store') }}" class="mt-8">
        <div class="flex justify-between">
            <a href="{{ route('installer.welcome') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg transition duration-200">
                Back
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200 {{ !$requirements['all_satisfied'] ? 'opacity-50 cursor-not-allowed' : '' }}" {{ !$requirements['all_satisfied'] ? 'disabled' : '' }}>
                Continue
            </button>
        </div>
    </form>
</div>
@endsection

@php
    $currentStep = 3;
@endphp
