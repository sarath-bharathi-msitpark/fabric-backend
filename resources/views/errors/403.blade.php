@extends('layouts.app')

@section('title', 'Access denied')
@section('header', 'Access denied')

@section('content')
<div class="max-w-xl mx-auto bg-white rounded-lg shadow-sm p-8 text-center">
    <div class="mx-auto w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-4">
        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
    <h2 class="text-lg font-semibold text-gray-800 mb-1">You don&rsquo;t have access to this page</h2>
    <p class="text-sm text-gray-500 mb-5">
        @auth
            Your account role <span class="font-medium text-gray-700">{{ auth()->user()->role }}</span> doesn&rsquo;t include permission to view this section. If you think this is a mistake, contact an administrator.
        @else
            Your account doesn&rsquo;t include permission to view this section. If you think this is a mistake, contact an administrator.
        @endauth
    </p>
    <a href="{{ auth()->check() ? route(auth()->user()->homeRoute()) : route('login') }}"
       class="inline-block px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">
        Go to my dashboard
    </a>
</div>
@endsection
