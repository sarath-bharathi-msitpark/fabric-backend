@extends('layouts.app')

@section('title', 'Cutting Audit Register')
@section('header', 'Cutting Audit Register')

@section('actions')
    @can('create', \App\Models\CuttingAudit::class)
        <a href="{{ route('admin.cutting.audits.create') }}" class="px-3 py-2 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700 whitespace-nowrap">+ New Audit</a>
    @endcan
    @can('export', \App\Models\CuttingAudit::class)
        <a href="{{ route('admin.cutting.audits.export', request()->query()) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50 whitespace-nowrap">Export Excel</a>
    @endcan
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4 print:hidden">
    <form method="GET" action="{{ route('admin.cutting.audits.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-3">
        <div class="sm:col-span-2">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Audit ID / Style# / PO# / LOT#..." class="w-full rounded-md border-gray-300 text-sm">
        </div>
        <div><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="From date"></div>
        <div><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="w-full rounded-md border-gray-300 text-sm" title="To date"></div>
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
            <select name="stage" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All stages">
                <option value="">All stages</option>
                @foreach($stages as $stage)<option value="{{ $stage }}" @selected(($filters['stage'] ?? '')===$stage)>{{ \App\Models\CuttingAudit::STAGE_LABELS[$stage] }}</option>@endforeach
            </select>
        </div>
        <div>
            <select name="result" class="js-select2 w-full rounded-md border-gray-300 text-sm" data-placeholder="All results">
                <option value="">All results</option>
                @foreach($results as $result)<option value="{{ $result }}" @selected(($filters['result'] ?? '')===$result)>{{ ucfirst(str_replace('_',' ', $result)) }}</option>@endforeach
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
            <a href="{{ route('admin.cutting.audits.index') }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-center hover:bg-gray-50">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]">
                <tr>
                    <th class="px-3 py-2 text-left">Audit ID</th>
                    <th class="px-3 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Table</th>
                    <th class="px-3 py-2 text-left">Buyer</th>
                    <th class="px-3 py-2 text-left">Style# / PO#</th>
                    <th class="px-3 py-2 text-left">Lay</th>
                    <th class="px-3 py-2 text-left">Cut Panel</th>
                    <th class="px-3 py-2 text-left">Bundling</th>
                    <th class="px-3 py-2 text-left">Rev</th>
                    <th class="px-3 py-2 text-left"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($audits as $audit)
                @php $st = fn($s) => $audit->stages->firstWhere('stage', $s); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $audit->audit_no }}</td>
                    <td class="px-3 py-2 text-xs">{{ $audit->audit_date?->format('d M Y') }}</td>
                    <td class="px-3 py-2">{{ $audit->cuttingTable?->table_no ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $audit->buyer?->buyer_name ?? '—' }}</td>
                    <td class="px-3 py-2 text-xs">{{ $audit->style_number ?? '—' }}<br><span class="text-gray-400">{{ $audit->po_number ?? '' }}</span></td>
                    @foreach(['lay','cut_panel','bundling'] as $stage)
                        @php $s = $st($stage); @endphp
                        <td class="px-3 py-2">
                            @if(!$s)<x-status-badge status="pending" label="—" />
                            @elseif(in_array($s->status, ['approved','special_approved']))<x-status-badge status="approved" :label="$s->status==='special_approved' ? 'Special' : 'Approved'" />
                            @elseif($s->status==='not_approved')<x-status-badge status="rejected" label="Not OK" />
                            @elseif($s->status==='special_pending')<x-status-badge status="yellow" label="Special?" />
                            @else<x-status-badge status="pending" :label="ucfirst($s->status)" />@endif
                        </td>
                    @endforeach
                    <td class="px-3 py-2 text-xs">{{ $audit->revision }}</td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <div class="inline-flex items-center gap-1">
                            <a href="{{ route('admin.cutting.audits.show', $audit) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100">View</a>
                            @can('update', $audit)
                            <a href="{{ route('admin.cutting.audits.edit', $audit) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-yellow-700 bg-yellow-50 hover:bg-yellow-100">Edit</a>
                            @endcan
                            @can('delete', $audit)
                            <form method="POST" action="{{ route('admin.cutting.audits.destroy', $audit) }}" onsubmit="return confirm('Delete audit {{ $audit->audit_no }}?')">@csrf @method('DELETE')
                                <button class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100">Delete</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No audits found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">{{ $audits->links() }}</div>
</div>
@endsection
