@extends('layouts.app')

@section('header', $title ?? 'Sewing Inline Inspection')

@section('content')
    <div class="bg-white rounded-lg shadow-sm p-10 text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-400 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h2 class="text-base font-semibold text-gray-800">{{ $title ?? 'Sewing Inline Inspection' }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ $message ?? 'This screen is scheduled for a later phase of the Sewing Inline Inspection module.' }}</p>
    </div>
@endsection
