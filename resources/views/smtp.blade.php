@extends('installer::layouts.master')

@section('title', 'SMTP Configuration - Installation Wizard')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">SMTP Configuration (Optional)</h2>
    <p class="text-gray-600 mb-6">Configure email settings for your application. You can skip this step and configure it later.</p>

    @if(isset($error))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif

    <form method="POST" action="{{ route('installer.smtp.store') }}" class="space-y-6">

        <!-- Mail Mailer -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Mail Driver
            </label>
            <select
                id="mail_mailer"
                name="mail_mailer"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                onchange="toggleSmtpFields()"
            >
                <option value="smtp" {{ $currentConfig['mail_mailer'] === 'smtp' ? 'selected' : '' }}>SMTP</option>
                <option value="sendmail" {{ $currentConfig['mail_mailer'] === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                <option value="mailgun" {{ $currentConfig['mail_mailer'] === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                <option value="ses" {{ $currentConfig['mail_mailer'] === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                <option value="postmark" {{ $currentConfig['mail_mailer'] === 'postmark' ? 'selected' : '' }}>Postmark</option>
                <option value="log" {{ $currentConfig['mail_mailer'] === 'log' ? 'selected' : '' }}>Log (Testing)</option>
            </select>
            <p class="mt-1 text-sm text-gray-500">Select your email service provider</p>
        </div>

        <!-- Mail Host -->
        <div id="smtp_fields_container">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Host
                </label>
                <input
                    type="text"
                    name="mail_host"
                    value="{{ $currentConfig['mail_host'] }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="smtp.gmail.com"
                >
                <p class="mt-1 text-sm text-gray-500">Your SMTP server hostname</p>
            </div>

            <!-- Mail Port -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Port
                </label>
                <input
                    type="number"
                    name="mail_port"
                    value="{{ $currentConfig['mail_port'] }}"
                    min="1"
                    max="65535"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="587"
                >
                <p class="mt-1 text-sm text-gray-500">Common ports: 587 (TLS), 465 (SSL), 25 (Plain)</p>
            </div>

            <!-- Mail Encryption -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Encryption
                </label>
                <select
                    name="mail_encryption"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="tls" {{ $currentConfig['mail_encryption'] === 'tls' ? 'selected' : '' }}>TLS</option>
                    <option value="ssl" {{ $currentConfig['mail_encryption'] === 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="none" {{ $currentConfig['mail_encryption'] === 'none' || $currentConfig['mail_encryption'] === 'null' ? 'selected' : '' }}>None</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Encryption method for secure connection</p>
            </div>

            <!-- Mail Username -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Username
                </label>
                <input
                    type="text"
                    name="mail_username"
                    value="{{ $currentConfig['mail_username'] }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="your-email@example.com"
                >
                <p class="mt-1 text-sm text-gray-500">Your SMTP authentication username (usually your email)</p>
            </div>

            <!-- Mail Password -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    SMTP Password
                </label>
                <input
                    type="password"
                    name="mail_password"
                    value="{{ $currentConfig['mail_password'] }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="••••••••"
                >
                <p class="mt-1 text-sm text-gray-500">Your SMTP authentication password</p>
            </div>
        </div>

        <!-- Mail From Address -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                From Email Address
            </label>
            <input
                type="email"
                name="mail_from_address"
                value="{{ $currentConfig['mail_from_address'] }}"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="noreply@example.com"
            >
            <p class="mt-1 text-sm text-gray-500">Default email address for outgoing emails</p>
        </div>

        <!-- Mail From Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                From Name
            </label>
            <input
                type="text"
                name="mail_from_name"
                value="{{ $currentConfig['mail_from_name'] }}"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="My Application"
            >
            <p class="mt-1 text-sm text-gray-500">Default sender name for outgoing emails</p>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between pt-6 border-t">
            <a href="{{ route('installer.license') }}"
               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg transition duration-200">
                Back
            </a>

            <div class="flex gap-3">
                <button type="submit" name="skip" value="1"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-8 rounded-lg transition duration-200">
                    Skip
                </button>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200">
                    Save & Continue
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@php
    $currentStep = 6;
@endphp

@push('scripts')
<script>
    function toggleSmtpFields() {
        const mailer = document.getElementById('mail_mailer').value;
        const smtpFieldsContainer = document.getElementById('smtp_fields_container');
        
        // Hide SMTP fields for 'log' and 'sendmail' drivers
        if (mailer === 'log' || mailer === 'sendmail') {
            smtpFieldsContainer.style.display = 'none';
        } else {
            smtpFieldsContainer.style.display = 'block';
        }
    }
    
    // Run on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleSmtpFields();
    });
</script>
@endpush
