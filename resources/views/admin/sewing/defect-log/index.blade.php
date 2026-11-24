@extends('layouts.app')

@section('title', 'Defect Log')
@section('header', 'Sewing Defect Log')

@section('content')
<form method="GET" action="{{ route('admin.sewing.defect-log.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
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
                    <td class="px-3 py-2 text-center">{{ $log->qty }}</td>
                    <td class="px-3 py-2">{{ $log->loggedBy->name ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-3 py-8 text-center text-gray-400">No defect logs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $logs->links() }}</div>
</div>
@endsection
