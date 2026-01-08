<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- CSRF is not available before APP_KEY / web middleware --}}
    @if(function_exists('csrf_token'))
        <meta name="csrf-token" content="{{ csrf_token() }}">
    @endif

    <title>@yield('title', 'Installation Wizard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen">
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                {{ config('installer.product.name', 'Application') }}
            </h1>
            <p class="text-gray-600 mt-2">Installation Wizard</p>
        </div>

        <!-- Progress Indicator -->
        @if(isset($currentStep))
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    @php
                        $installer = app(\SoftCortex\Installer\Services\InstallerService::class);
                        $steps = [
                            ['name' => 'Welcome', 'route' => 'installer.welcome'],
                            ['name' => 'App Config', 'route' => 'installer.app-config'],
                            ['name' => 'Requirements', 'route' => 'installer.requirements'],
                            ['name' => 'Database', 'route' => 'installer.database'],
                            ['name' => 'License', 'route' => 'installer.license'],
                            ['name' => 'Admin', 'route' => 'installer.admin'],
                            ['name' => 'Finalize', 'route' => 'installer.finalize'],
                        ];
                    @endphp

                    @foreach($steps as $index => $step)
                        @php
                            $stepNumber = $index + 1;
                            $isCompleted = $installer->isStepCompleted($stepNumber);
                            $isAccessible = $installer->isStepAccessible($stepNumber);
                            $isCurrent = $stepNumber == $currentStep;
                        @endphp

                        <div class="flex-1 text-center">
                            <div class="relative">
                                @if($isAccessible)
                                    <a href="{{ route($step['route']) }}" class="block">
                                        <div class="w-10 h-10 mx-auto rounded-full transition-all
                                            {{ $isCompleted ? 'bg-green-600 text-white' : ($isCurrent ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600') }}
                                            flex items-center justify-center font-semibold hover:ring-2 hover:ring-offset-2
                                            {{ $isCompleted ? 'hover:ring-green-400' : 'hover:ring-blue-400' }}">
                                            @if($isCompleted)
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            @else
                                                {{ $stepNumber }}
                                            @endif
                                        </div>
                                        <div class="text-xs mt-2
                                            {{ $isCurrent ? 'text-blue-600 font-semibold' : ($isCompleted ? 'text-green-600' : 'text-gray-600') }}">
                                            {{ $step['name'] }}
                                        </div>
                                    </a>
                                @else
                                    <div class="w-10 h-10 mx-auto rounded-full bg-gray-200 text-gray-400
                                        flex items-center justify-center font-semibold cursor-not-allowed">
                                        {{ $stepNumber }}
                                    </div>
                                    <div class="text-xs mt-2 text-gray-400">
                                        {{ $step['name'] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($index < 6)
                            <div class="flex-1 h-1
                                {{ $isCompleted ? 'bg-green-600' : (($index + 1) < $currentStep ? 'bg-blue-600' : 'bg-gray-300') }}">
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-lg p-8">

            {{-- Validation errors (only if web/session exists) --}}
            @if(isset($errors) && $errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Success message (guarded) --}}
            @if(function_exists('session') && session()->has('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-600 text-sm">
            <p>
                {{ config('installer.product.name', 'Application') }}
                v{{ config('installer.product.version', '1.0.0') }}
            </p>
        </div>

    </div>
</div>

@stack('scripts')
</body>
</html>
