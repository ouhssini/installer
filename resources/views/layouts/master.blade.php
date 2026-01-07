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
                    @foreach(['Welcome', 'App Config', 'Requirements', 'Database', 'License', 'Admin', 'Finalize'] as $index => $step)
                        <div class="flex-1 text-center">
                            <div class="relative">
                                <div class="w-10 h-10 mx-auto rounded-full
                                    {{ ($index + 1) <= $currentStep ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600' }}
                                    flex items-center justify-center font-semibold">
                                    {{ $index + 1 }}
                                </div>
                                <div class="text-xs mt-2
                                    {{ ($index + 1) == $currentStep ? 'text-blue-600 font-semibold' : 'text-gray-600' }}">
                                    {{ $step }}
                                </div>
                            </div>
                        </div>

                        @if($index < 6)
                            <div class="flex-1 h-1
                                {{ ($index + 1) < $currentStep ? 'bg-blue-600' : 'bg-gray-300' }}">
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
