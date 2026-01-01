@extends('installer::layouts.master')

@section('title', 'License Verification - Installation Wizard')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">License Verification</h2>
    
    @if($licenseEnabled)
    <div class="mb-6">
        <p class="text-gray-700 mb-4">Please enter your Envato purchase code to verify your license.</p>
        <p class="text-sm text-gray-600">You can find your purchase code in your Envato account under Downloads.</p>
    </div>

    <form method="POST" action="{{ route('installer.license.store') }}" id="licenseForm">
        @csrf
        
        <div class="space-y-4">
            <div>
                <label for="purchase_code" class="block text-gray-700 font-semibold mb-2">Purchase Code</label>
                <input type="text" name="purchase_code" id="purchase_code" value="{{ old('purchase_code') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
            </div>
        </div>

        <div class="mt-6">
            <button type="button" id="verifyLicense" class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-200">
                Verify License
            </button>
            <span id="verifyResult" class="ml-4"></span>
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('installer.database') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg transition duration-200">
                Back
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200">
                Continue
            </button>
        </div>
    </form>
    @else
    <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded">
        <p>License verification is disabled. Click Continue to proceed.</p>
    </div>

    <form method="POST" action="{{ route('installer.license.store') }}">
        @csrf
        <div class="flex justify-between mt-8">
            <a href="{{ route('installer.database') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg transition duration-200">
                Back
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200">
                Continue
            </button>
        </div>
    </form>
    @endif
</div>
@endsection

@php
    $currentStep = 5;
@endphp

@if($licenseEnabled)
@push('scripts')
<script>
document.getElementById('verifyLicense').addEventListener('click', function() {
    const button = this;
    const result = document.getElementById('verifyResult');
    const purchaseCode = document.getElementById('purchase_code').value;
    
    if (!purchaseCode) {
        result.innerHTML = '<span class="text-red-600 font-semibold">Please enter a purchase code</span>';
        return;
    }
    
    button.disabled = true;
    button.textContent = 'Verifying...';
    result.textContent = '';
    
    fetch('{{ route('installer.license.verify') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ purchase_code: purchaseCode })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            result.innerHTML = '<span class="text-green-600 font-semibold">✓ License verified!</span>';
        } else {
            result.innerHTML = '<span class="text-red-600 font-semibold">✗ ' + data.message + '</span>';
        }
    })
    .catch(error => {
        result.innerHTML = '<span class="text-red-600 font-semibold">✗ Verification failed</span>';
    })
    .finally(() => {
        button.disabled = false;
        button.textContent = 'Verify License';
    });
});
</script>
@endpush
@endif
