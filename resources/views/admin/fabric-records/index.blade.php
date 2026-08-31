@extends('layouts.app')

@section('title', 'Fabric Records')
@section('header', 'Fabric Records')

@section('actions')
    @can('export', \App\Models\FabricRecord::class)
    <a href="{{ route('admin.fabric-records.export', request()->query()) }}" class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white hover:bg-green-700">Export to Excel</a>
    @endcan
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm p-4 mb-4 print:hidden">
    <form method="GET" action="{{ route('admin.fabric-records.index') }}" class="grid grid-cols-2 md:grid-cols-6 gap-3">
        <select name="buyer_id" class="rounded-md border-gray-300 text-sm"><option value="">All Buyers</option>
            @foreach($buyers as $b)<option value="{{ $b->id }}" @selected(request('buyer_id')==$b->id)>{{ $b->buyer_name }}</option>@endforeach
        </select>
        <select name="style_id" class="rounded-md border-gray-300 text-sm"><option value="">All Styles</option>
            @foreach($styles as $s)<option value="{{ $s->id }}" @selected(request('style_id')==$s->id)>{{ $s->style_number }}</option>@endforeach
        </select>
        <select name="supplier_id" class="rounded-md border-gray-300 text-sm"><option value="">All Suppliers</option>
            @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(request('supplier_id')==$s->id)>{{ $s->supplier_name }}</option>@endforeach
        </select>
        <select name="fabric_type" class="rounded-md border-gray-300 text-sm"><option value="">All Fabric Types</option>
            @foreach($fabricTypes as $ft)<option value="{{ $ft }}" @selected(request('fabric_type')==$ft)>{{ $ft }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 text-sm" placeholder="From">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 text-sm" placeholder="To">
        <div class="col-span-2 md:col-span-6 flex gap-2">
            <button type="submit" class="px-4 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply Filters</button>
            <a href="{{ route('admin.fabric-records.index') }}" class="px-4 py-1.5 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2 text-left">Lot No</th>
                    <th class="px-3 py-2 text-left">Buyer</th><th class="px-3 py-2 text-left">Style</th>
                    <th class="px-3 py-2 text-left">Supplier</th><th class="px-3 py-2 text-left">Fabric Type</th>
                    <th class="px-3 py-2 text-left">Color</th><th class="px-3 py-2 text-right">Ordered</th>
                    <th class="px-3 py-2 text-right">Received</th><th class="px-3 py-2 text-right">Pass %</th>
                    <th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $r)
                @php
                    $passPct = (float) ($r->inspection?->pass_pct ?? 0);
                    $status = $passPct >= 98 ? 'green' : ($passPct >= 88 ? 'yellow' : 'red');
                    if ($r->received_kg < $r->ordered_kg) $status = $status === 'green' ? 'yellow' : $status;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 text-xs">{{ $r->record_date?->format('Y-m-d') }}</td>
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $r->lot_no }}</td>
                    <td class="px-3 py-2">{{ $r->buyer?->buyer_name }}</td>
                    <td class="px-3 py-2">{{ $r->style?->style_number }}</td>
                    <td class="px-3 py-2">{{ $r->supplier?->supplier_name }}</td>
                    <td class="px-3 py-2">{{ $r->fabric_type }}</td>
                    <td class="px-3 py-2">{{ $r->color }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format((float)$r->ordered_kg, 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format((float)$r->received_kg, 2) }}</td>
                    <td class="px-3 py-2 text-right">{{ $r->inspection?->pass_pct ? number_format((float)$r->inspection->pass_pct, 2) : '-' }}</td>
                    <td class="px-3 py-2"><x-status-badge :status="$status" /></td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <a href="{{ route('admin.fabric-records.show', $r) }}" title="View" class="text-gray-600 hover:text-blue-600">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.46 12C3.73 7.94 7.52 5 12 5s8.27 2.94 9.54 7c-1.27 4.06-5.06 7-9.54 7s-8.27-2.94-9.54-7z"/></svg>
                        </a>
                        @can('update', $r)
                        <a href="{{ route('admin.fabric-records.edit', $r) }}" title="Edit" class="text-gray-600 hover:text-yellow-600 ml-1">
                            <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.41-9.41a2 2 0 112.83 2.83L11.83 15H9v-2.83l8.59-8.58z"/></svg>
                        </a>
                        @endcan
                        @can('delete', $r)
                        <x-confirm-modal :id="'del-'.$r->id" title="Delete Lot {{ $r->lot_no }}?" method="DELETE" :action="route('admin.fabric-records.destroy', $r)" confirm-text="Delete">
                            <button title="Delete" class="text-gray-600 hover:text-red-600 ml-1">
                                <svg class="w-4 h-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.87 12.14A2 2 0 0116.14 21H7.86a2 2 0 01-2-1.86L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                            </button>
                            <x-slot:content>This will permanently delete lot <strong>{{ $r->lot_no }}</strong> and its inspection/defect data. This cannot be undone.</x-slot:content>
                        </x-confirm-modal>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" class="px-3 py-8 text-center text-gray-400">No fabric records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-100">
        {{ $records->withQueryString()->links() }}
    </div>
</div>
@endsection
