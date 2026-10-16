@extends('layouts.app')

@section('title', 'Cutting Reports')
@section('header', 'Cutting QMS Reports')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    @foreach($types as $key => $label)
    <a href="{{ route('admin.cutting.reports.show', $key) }}" class="bg-white rounded-lg shadow-sm p-5 hover:shadow-md transition flex items-center justify-between">
        <div>
            <div class="font-semibold text-gray-800">{{ $label }}</div>
            <div class="text-xs text-gray-500 mt-1">View, filter and export to Excel / PDF</div>
        </div>
        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    @endforeach
</div>
@endsection
