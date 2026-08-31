@extends('layouts.app')

@section('title', 'Admin Overview')
@section('header', 'Admin Overview')

@section('actions')
    @can('upload data')
    <a href="{{ route('admin.upload.index') }}" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">Go to Data Upload</a>
    @endcan
    <a href="{{ route('admin.fabric-records.index') }}" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">View Fabric Records</a>
@endsection

@section('content')
<h2 class="text-sm font-semibold text-gray-500 mb-3">Current Month Summary ({{ now()->format('F Y') }})</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-blue-500 p-4">
        <div class="text-xs uppercase text-gray-500">Total Ordered (kg)</div>
        <div class="text-2xl font-bold text-blue-600 mt-1">{{ number_format($totalOrdered, 2) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-cyan-500 p-4">
        <div class="text-xs uppercase text-gray-500">Total Received (kg)</div>
        <div class="text-2xl font-bold text-cyan-600 mt-1">{{ number_format($totalReceived, 2) }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border-l-4 border-green-500 p-4">
        <div class="text-xs uppercase text-gray-500">Total Approved (kg)</div>
        <div class="text-2xl font-bold text-green-600 mt-1">{{ number_format($totalApproved, 2) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Open Alerts</h3>
            <span class="px-2 py-0.5 rounded-full text-xs {{ $openAlerts > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">{{ $openAlerts }}</span>
        </div>
        <p class="text-sm text-gray-500">Active alerts requiring attention. Visit the dashboard to view and resolve.</p>
        <a href="{{ route('dashboard') }}" class="mt-3 inline-block text-blue-600 text-sm hover:underline">Go to Dashboard &rarr;</a>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Recent Uploads</h3>
        @forelse($recentUploads as $batch)
        <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
            <div>
                <div class="text-sm font-medium text-gray-800">{{ $batch->file_name }}</div>
                <div class="text-xs text-gray-400">by {{ $batch->uploader?->name }} &middot; {{ $batch->created_at->diffForHumans() }}</div>
            </div>
            <x-status-badge :status="$batch->status" />
        </div>
        @empty
        <p class="text-sm text-gray-400 py-2">No uploads yet.</p>
        @endforelse
    </div>
</div>
@endsection
