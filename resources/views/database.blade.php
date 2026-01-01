@extends('installer::layouts.master')

@section('title', 'Database Configuration - Installation Wizard')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Database Configuration</h2>
    
    <form method="POST" action="{{ route('installer.database.store') }}" id="databaseForm">
        @csrf
        
        <div class="space-y-4">
            <div>
                <label for="connection" class="block text-gray-700 font-semibold mb-2">Database Type</label>
                <select name="connection" id="connection" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="sqlite" {{ old('connection') === 'sqlite' ? 'selected' : '' }}>SQLite (File-based, no server required)</option>
                    <option value="mysql" {{ old('connection', 'mysql') === 'mysql' ? 'selected' : '' }}>MySQL / MariaDB</option>
                    <option value="pgsql" {{ old('connection') === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
                </select>
                <p class="text-sm text-gray-600 mt-1">SQLite is recommended for small to medium applications</p>
            </div>

            <div id="sqliteFields" style="display: none;">
                <div>
                    <label for="sqlite_database" class="block text-gray-700 font-semibold mb-2">Database File Path</label>
                    <input type="text" name="database" id="sqlite_database" value="{{ old('database', database_path('database.sqlite')) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-sm text-gray-600 mt-1">Leave default for automatic setup</p>
                </div>
            </div>

            <div id="serverFields">
                <div>
                    <label for="host" class="block text-gray-700 font-semibold mb-2">Database Host</label>
                    <input type="text" name="host" id="host" value="{{ old('host', '127.0.0.1') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="port" class="block text-gray-700 font-semibold mb-2">Database Port</label>
                    <input type="number" name="port" id="port" value="{{ old('port', '3306') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="database" class="block text-gray-700 font-semibold mb-2">Database Name</label>
                    <input type="text" name="database" id="database" value="{{ old('database') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="username" class="block text-gray-700 font-semibold mb-2">Database Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-gray-700 font-semibold mb-2">Database Password</label>
                    <input type="password" name="password" id="password" value="{{ old('password') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
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
const connectionSelect = document.getElementById('connection');
const sqliteFields = document.getElementById('sqliteFields');
const serverFields = document.getElementById('serverFields');
const portInput = document.getElementById('port');

function toggleFields() {
    const connection = connectionSelect.value;
    
    if (connection === 'sqlite') {
        sqliteFields.style.display = 'block';
        serverFields.style.display = 'none';
        
        // Remove required from server fields
        document.getElementById('host').removeAttribute('required');
        document.getElementById('port').removeAttribute('required');
        document.getElementById('database').removeAttribute('required');
        document.getElementById('username').removeAttribute('required');
        
        // Add required to sqlite field
        document.getElementById('sqlite_database').setAttribute('required', 'required');
    } else {
        sqliteFields.style.display = 'none';
        serverFields.style.display = 'block';
        
        // Add required to server fields
        document.getElementById('host').setAttribute('required', 'required');
        document.getElementById('port').setAttribute('required', 'required');
        document.getElementById('database').setAttribute('required', 'required');
        document.getElementById('username').setAttribute('required', 'required');
        
        // Remove required from sqlite field
        document.getElementById('sqlite_database').removeAttribute('required');
        
        // Update default port
        if (connection === 'pgsql') {
            portInput.value = '5432';
        } else {
            portInput.value = '3306';
        }
    }
}

connectionSelect.addEventListener('change', toggleFields);
toggleFields(); // Initialize on page load

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
