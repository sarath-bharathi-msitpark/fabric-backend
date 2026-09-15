@extends('layouts.app')

@section('title', 'Profile')
@section('header', 'Profile')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    {{-- User summary card --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 rounded-full bg-slate-800 text-cyan-400 flex items-center justify-center text-2xl font-bold">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-800">{{ $user->name }}</h3>
                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                <span class="inline-block mt-1 px-2 py-0.5 rounded bg-slate-100 text-[10px] uppercase font-medium text-gray-600">{{ $user->role }}</span>
            </div>
        </div>
        <dl class="space-y-2 text-sm border-t border-gray-100 pt-4">
            <div class="flex justify-between"><dt class="text-gray-500">Member since</dt><dd class="text-gray-800">{{ $user->created_at?->format('d M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Last updated</dt><dd class="text-gray-800">{{ $user->updated_at?->format('d M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Email verified</dt><dd class="text-gray-800">{{ $user->email_verified_at ? 'Yes' : 'No' }}</dd></div>
        </dl>
    </div>

    {{-- Profile information form --}}
    <div class="bg-white rounded-lg shadow-sm p-6 lg:col-span-2">
        <h3 class="text-sm font-semibold text-gray-700 mb-1">Profile Information</h3>
        <p class="text-xs text-gray-500 mb-4">Update your account's profile information and email address.</p>
        @include('profile.partials.update-profile-information-form')
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Password update --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-1">Update Password</h3>
        <p class="text-xs text-gray-500 mb-4">Ensure your account is using a long, random password to stay secure.</p>
        @include('profile.partials.update-password-form')
    </div>

    {{-- Delete account --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-1">Delete Account</h3>
        <p class="text-xs text-gray-500 mb-4">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
        @include('profile.partials.delete-user-form')
    </div>
</div>
@endsection
