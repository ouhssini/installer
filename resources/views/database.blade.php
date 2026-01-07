@extends('installer::layouts.master')

@section('title', 'Database Configuration - Installation Wizard')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Database Configuration</h2>

    <form method="POST" action="{{ route('installer.database.store') }}" id="databaseForm">

        <div class="space-y-4">
            <div>
                <label for="host" class="block text-gray-700 font-semibold mb-2">Database Host</label>
                <input type="text" name="host" id="host" value="127.0.0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label for="port" class="block text-gray-700 font-semibold mb-2">Database Port</label>
                <input type="number" name="port" id="port" value="3306" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label for="database" class="block text-gray-700 font-semibold mb-2">Database Name</label>
                <input type="text" name="database" id="database" value="" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label for="username" class="block text-gray-700 font-semibold mb-2">Database Username</label>
                <input type="text" name="username" id="username" value="" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label for="password" class="block text-gray-700 font-semibold mb-2">Database Password</label>
                <input type="password" name="password" id="password" value="" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-6">
            <button type="button" id="testConnection" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                Test Connection
            </button>
            <span id="testResult" class="ml-4"></span>
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('installer.app-config') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg transition duration-200">
                Back
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200">
                Continue
            </button>
        </div>
    </form>
</div>
@endsection

@php
    $currentStep = 4;
@endphp

@push('scripts')
<script>
document.getElementById('testConnection').addEventListener('click', function() {
    const button = this;
    const result = document.getElementById('testResult');

    button.disabled = true;
    button.textContent = 'Testing...';
    result.textContent = '';

    const formData = new FormData(document.getElementById('databaseForm'));

    fetch('{{ route('installer.database.test') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            result.innerHTML = '<span class="text-green-600 font-semibold">✓ Connection successful!</span>';
        } else {
            result.innerHTML = '<span class="text-red-600 font-semibold">✗ ' + data.message + '</span>';
        }
    })
    .catch(error => {
        result.innerHTML = '<span class="text-red-600 font-semibold">✗ Connection failed</span>';
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = 'Test Connection';
    });
});
</script>
@endpush
