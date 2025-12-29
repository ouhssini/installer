@extends('installer::layouts.master')

@section('title', 'Admin Account - Installation Wizard')

@section('content')
<div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Admin Account</h2>
    
    <p class="text-gray-700 mb-6">Create your administrator account to manage the application.</p>

    <form method="POST" action="{{ route('installer.admin.store') }}">
        @csrf
        
        <div class="space-y-4">
            <div>
                <label for="name" class="block text-gray-700 font-semibold mb-2">Full Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label for="email" class="block text-gray-700 font-semibold mb-2">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>

            <div>
                <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                <input type="password" name="password" id="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required minlength="8">
                <p class="text-sm text-gray-600 mt-1">Minimum 8 characters</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required minlength="8">
            </div>
        </div>

        <div class="flex justify-between mt-8">
            <a href="{{ route('installer.license') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-8 rounded-lg transition duration-200">
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
    $currentStep = 5;
@endphp
