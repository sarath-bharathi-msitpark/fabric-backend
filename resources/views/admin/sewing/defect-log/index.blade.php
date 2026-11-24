@extends('layouts.app')

@section('title', 'Defect Log')
@section('header', 'Sewing Defect Log')

@section('actions')
@can('export sewing reports')
<a href="{{ route('admin.sewing.defect-log.export', ['format' => 'xlsx'] + request()->query()) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Export Excel</a>
<a href="{{ route('admin.sewing.defect-log.export', ['format' => 'pdf'] + request()->query()) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Export PDF</a>
@endcan
@endsection

@section('content')
@if($summary->isNotEmpty())
<div class="mb-4">
    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 mb-2">Top defects by name (current filters)</div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
        @foreach($summary as $row)
        <div class="bg-white rounded-lg shadow-sm p-3">
            <div class="flex items-center justify-between">
                <span class="font-mono text-xs text-gray-500">{{ $row->capa_code }}</span>
                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ $row->defect_type }}</span>
            </div>
            <div class="mt-1 text-sm font-medium text-gray-800 truncate" title="{{ $row->defect_name }}">{{ $row->defect_name }}</div>
            <div class="mt-1 text-xs text-gray-500">{{ $row->total }} pcs · {{ $row->occurrences }} entries</div>
        </div>
        @endforeach
    </div>
</div>
@endif

<form method="GET" action="{{ route('admin.sewing.defect-log.index') }}" class="mb-4 bg-white rounded-lg shadow-sm p-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Defect type</label>
        <select name="type" class="rounded-md border-gray-300 text-sm">
            <option value="">All</option>
            @foreach($types as $key => $label)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">CAPA code</label>
        <input type="text" name="capa_code" value="{{ request('capa_code') }}" placeholder="e.g. S3" class="rounded-md border-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Defect name</label>
        <input type="text" name="defect_name" value="{{ request('defect_name') }}" placeholder="Search name" class="rounded-md border-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 text-sm">
    </div>
    <button class="px-4 py-2 text-sm rounded-md bg-slate-700 text-white">Filter</button>
    <a href="{{ route('admin.sewing.defect-log.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300">Reset</a>
</form>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Sheet</th>
                    <th class="px-3 py-2 text-left">Round</th>
                    <th class="px-3 py-2 text-left">Operation</th>
                    <th class="px-3 py-2 text-left">Type</th>
                    <th class="px-3 py-2 text-left">CAPA</th>
                    <th class="px-3 py-2 text-left">Defect</th>
                    <th class="px-3 py-2 text-center">Photo</th>
                    <th class="px-3 py-2 text-center">Qty</th>
                    <th class="px-3 py-2 text-left">Logged by</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr>
                    <td class="px-3 py-2 font-mono">{{ $log->sheet->sheet_no ?? '—' }}</td>
                    <td class="px-3 py-2">R{{ $log->round_no }}</td>
                    <td class="px-3 py-2">{{ $log->operation->operation_name ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $log->defect_type }}</td>
                    <td class="px-3 py-2 font-mono">{{ $log->capa_code }}</td>
                    <td class="px-3 py-2">{{ $log->defect_name }}</td>
                    <td class="px-3 py-2 text-center">
                        @if($log->photo_path)<a href="{{ \Illuminate\Support\Facades\Storage::url($log->photo_path) }}" target="_blank" class="text-blue-600 hover:underline">view</a>@else—@endif
                    </td>
                    <td class="px-3 py-2 text-center">{{ $log->qty }}</td>
                    <td class="px-3 py-2">{{ $log->loggedBy->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-3 py-8 text-center text-gray-400">No defect logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $logs->links() }}</div>
</div>
@endsection
