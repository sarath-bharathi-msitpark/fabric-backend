@extends('layouts.app')

@section('title', 'Cutting QMS Dashboard')
@section('header', 'Cutting QMS Dashboard')

@section('actions')
    <a href="{{ route('admin.cutting.audits.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Audit Register</a>
    <a href="{{ route('admin.cutting.reports.index') }}" class="px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">Reports</a>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4">
    <form method="GET" action="{{ route('admin.cutting.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
        <div><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="From"></div>
        <div><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="To"></div>
        <div>
            <select name="buyer_id" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All buyers">
                <option value="">All buyers</option>
                @foreach($buyers as $buyer)<option value="{{ $buyer->id }}" @selected(($filters['buyer_id'] ?? '')==$buyer->id)>{{ $buyer->buyer_name }}</option>@endforeach
            </select>
        </div>
        <div>
            <select name="cutting_table_id" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All tables">
                <option value="">All tables</option>
                @foreach($tables as $table)<option value="{{ $table->id }}" @selected(($filters['cutting_table_id'] ?? '')==$table->id)>{{ $table->table_no }}</option>@endforeach
            </select>
        </div>
        <div>
            <select name="shift" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All shifts">
                <option value="">All shifts</option>
                @foreach($shifts as $shift)<option value="{{ $shift }}" @selected(($filters['shift'] ?? '')===$shift)>{{ $shift }}</option>@endforeach
            </select>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply</button>
            <a href="{{ route('admin.cutting.dashboard') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Reset</a>
        </div>
    </form>
</div>

<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-4">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-[10px] uppercase text-gray-400">Audits Today</div>
        <div class="text-2xl font-semibold text-gray-800">{{ $kpis['audits_started_today'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-[10px] uppercase text-gray-400">Lay Approval %</div>
        <div class="text-2xl font-semibold text-green-600">{{ $kpis['lay_approval_pct'] }}%</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-[10px] uppercase text-gray-400">Cut Panel Approval %</div>
        <div class="text-2xl font-semibold text-green-600">{{ $kpis['cut_panel_approval_pct'] }}%</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-[10px] uppercase text-gray-400">Bundles Issued</div>
        <div class="text-2xl font-semibold text-blue-600">{{ $kpis['bundles_issued'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-[10px] uppercase text-gray-400">Total Audits</div>
        <div class="text-2xl font-semibold text-gray-800">{{ $kpis['audits_total'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="text-[10px] uppercase text-gray-400">Pending Sign-offs</div>
        <div class="text-2xl font-semibold text-orange-600">{{ $kpis['pending_signoffs'] }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    @foreach($stageCounts as $stage => $counts)
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 text-sm mb-3">{{ \App\Models\CuttingAudit::STAGE_LABELS[$stage] }}</h3>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div class="rounded bg-green-50 p-2"><div class="text-[10px] uppercase text-green-700">Approved</div><div class="font-semibold text-green-800">{{ $counts['approved'] }}</div></div>
            <div class="rounded bg-red-50 p-2"><div class="text-[10px] uppercase text-red-700">Not Approved</div><div class="font-semibold text-red-800">{{ $counts['not_approved'] }}</div></div>
            <div class="rounded bg-yellow-50 p-2"><div class="text-[10px] uppercase text-yellow-700">Special</div><div class="font-semibold text-yellow-800">{{ $counts['special_approval'] }}</div></div>
            <div class="rounded bg-gray-50 p-2"><div class="text-[10px] uppercase text-gray-600">Pending</div><div class="font-semibold text-gray-700">{{ $counts['pending'] }}</div></div>
        </div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Top Not OK Checkpoints (7 days)</h3></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]"><tr><th class="px-3 py-2 text-left">Checkpoint</th><th class="px-3 py-2 text-left">Count</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topNotOk as $row)
                    <tr><td class="px-3 py-2">{{ $row->checkpoint_name }}</td><td class="px-3 py-2">{{ $row->total }}</td></tr>
                    @empty
                    <tr><td colspan="2" class="px-3 py-6 text-center text-gray-400">No defects recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Pending Sign-offs</h3></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]"><tr><th class="px-3 py-2 text-left">Audit</th><th class="px-3 py-2 text-left">Stage</th><th class="px-3 py-2 text-left">Waiting on</th><th class="px-3 py-2 text-left">Waiting</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pending as $stage)
                    <tr>
                        <td class="px-3 py-2"><a href="{{ route('admin.cutting.audits.show', $stage->audit) }}" class="text-blue-600 underline">{{ $stage->audit->audit_no }}</a></td>
                        <td class="px-3 py-2 text-xs">{{ \App\Models\CuttingAudit::STAGE_LABELS[$stage->stage] }}</td>
                        <td class="px-3 py-2 text-xs">{{ ucwords(str_replace('_',' ', $stage->expected_role)) }}</td>
                        <td class="px-3 py-2 text-xs">{{ $stage->waiting_since?->diffForHumans(null, true) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Nothing pending.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden mt-4">
    <div class="px-4 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Cutting Table Status</h3></div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]"><tr><th class="px-3 py-2 text-left">Audit</th><th class="px-3 py-2 text-left">Table</th><th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2 text-left">Current Stage</th><th class="px-3 py-2 text-left">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tableStatus as $row)
                <tr>
                    <td class="px-3 py-2"><a href="{{ route('admin.cutting.audits.show', $row['audit']) }}" class="text-blue-600 underline">{{ $row['audit']->audit_no }}</a></td>
                    <td class="px-3 py-2">{{ $row['table'] }}</td>
                    <td class="px-3 py-2 text-xs">{{ $row['audit']->audit_date?->format('d M Y') }}</td>
                    <td class="px-3 py-2 text-xs">{{ \App\Models\CuttingAudit::STAGE_LABELS[$row['stage']] ?? ucfirst($row['stage']) }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$row['status'] === 'closed' ? 'completed' : 'in_progress'" :label="ucfirst($row['status'])" /></td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">No audits yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
