@extends('layouts.app')

@section('title', 'Lot ' . $fabric_record->lot_no)
@section('header', 'Fabric Record Detail — ' . $fabric_record->lot_no)

@section('actions')
    <a href="{{ route('admin.fabric-records.index') }}" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Back to List</a>
    @if($fabric_record->rolls->isNotEmpty())
    <div class="relative inline-block" x-data="{ open: false }">
        <button @click="open = !open" class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white hover:bg-green-700 inline-flex items-center gap-1">
            4-Point Inspection Report
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" @click.outside="open = false" x-transition
             class="absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-md shadow-lg z-50">
            <a href="{{ route('admin.fabric-records.inspection-report', ['fabric_record' => $fabric_record, 'format' => 'xlsx']) }}" class="block px-3 py-2 text-xs hover:bg-gray-50">Excel (.xlsx)</a>
            <a href="{{ route('admin.fabric-records.inspection-report', ['fabric_record' => $fabric_record, 'format' => 'pdf']) }}" class="block px-3 py-2 text-xs hover:bg-gray-50 border-t border-gray-100" target="_blank">PDF</a>
            <a href="{{ route('admin.fabric-records.inspection-report', ['fabric_record' => $fabric_record, 'format' => 'csv']) }}" class="block px-3 py-2 text-xs hover:bg-gray-50 border-t border-gray-100">CSV</a>
        </div>
    </div>
    @endif
    @can('update', $fabric_record)
    <a href="{{ route('admin.fabric-records.edit', $fabric_record) }}" class="px-3 py-1.5 text-xs rounded-md bg-yellow-600 text-white hover:bg-yellow-700">QC Inspection</a>
    @endcan
@endsection

@section('content')
@php $insp = $fabric_record->inspection; @endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 lg:col-span-2">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Record Information</h3>
        <div class="flex gap-4">
            @if($fabric_record->fabric_image_path)
            <img src="{{ asset('storage/' . $fabric_record->fabric_image_path) }}" alt="Fabric" class="w-24 h-24 object-cover rounded-lg border border-gray-200 flex-shrink-0">
            @endif
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm flex-1">
            <div><dt class="text-xs text-gray-500">Date</dt><dd class="text-gray-800">{{ $fabric_record->record_date?->format('Y-m-d') }}</dd></div>
            <div><dt class="text-xs text-gray-500">Lot No</dt><dd class="text-gray-800 font-medium">{{ $fabric_record->lot_no }}</dd></div>
            <div><dt class="text-xs text-gray-500">Buyer</dt><dd class="text-gray-800">{{ $fabric_record->buyer?->buyer_name }}</dd></div>
            <div><dt class="text-xs text-gray-500">Style</dt><dd class="text-gray-800">{{ $fabric_record->style?->style_number }}</dd></div>
            <div><dt class="text-xs text-gray-500">Supplier</dt><dd class="text-gray-800">{{ $fabric_record->supplier?->supplier_name }}</dd></div>
            <div><dt class="text-xs text-gray-500">Fabric Type</dt><dd class="text-gray-800">{{ $fabric_record->fabric_type }}</dd></div>
            <div><dt class="text-xs text-gray-500">Color</dt><dd class="text-gray-800">{{ $fabric_record->color }}</dd></div>
            <div><dt class="text-xs text-gray-500">Uploaded By</dt><dd class="text-gray-800">{{ $fabric_record->uploader?->name }}</dd></div>
            </dl>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Quantities (kg)</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Ordered</span><span class="font-medium">{{ number_format((float)$fabric_record->ordered_kg, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Received</span><span class="font-medium">{{ number_format((float)$fabric_record->received_kg, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Inspected</span><span class="font-medium">{{ number_format((float)($insp?->inspected_kg ?? 0), 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Approved</span><span class="font-medium text-green-700">{{ number_format((float)($insp?->approved_kg ?? 0), 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Rejected</span><span class="font-medium text-red-700">{{ number_format((float)($insp?->rejected_kg ?? 0), 2) }}</span></div>
            <div class="flex justify-between pt-2 border-t border-gray-100"><span class="text-gray-500">Pass %</span><span class="font-medium"><x-status-badge :status="$insp && (float)$insp->pass_pct >= 98 ? 'green' : ($insp && (float)$insp->pass_pct >= 88 ? 'yellow' : 'red')" :label="number_format((float)($insp?->pass_pct ?? 0), 2).' %'" /></span></div>
            <div class="flex justify-between"><span class="text-gray-500">Shade Status</span><x-status-badge :status="$insp?->shade_status ?? 'pending'" /></div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Quality Metrics</h3>
        @if($quality)
        <table class="min-w-full text-sm">
            <thead class="text-xs text-gray-500 uppercase"><tr><th class="px-2 py-2 text-left">Metric</th><th class="px-2 py-2 text-right">Value</th><th class="px-2 py-2 text-right">Target</th><th class="px-2 py-2 text-left">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($quality as $key => $m)
                <tr><td class="px-2 py-2">{{ ucfirst(str_replace('_',' ',$key)) }}</td><td class="px-2 py-2 text-right">{{ number_format((float)$m['value'], 2) }}</td><td class="px-2 py-2 text-right">{{ $m['target'] }}</td><td class="px-2 py-2"><x-status-badge :status="$m['status']" /></td></tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-sm text-gray-400">No inspection data available.</p>
        @endif
    </div>

    @if($fabric_record->rolls->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm p-4 lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Inspection Rolls (4-Point System)</h3>
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="text-xs text-green-600 hover:text-green-800 inline-flex items-center gap-1">
                    Download Report
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition
                     class="absolute right-0 mt-1 w-36 bg-white border border-gray-200 rounded-md shadow-lg z-50">
                    <a href="{{ route('admin.fabric-records.inspection-report', ['fabric_record' => $fabric_record, 'format' => 'xlsx']) }}" class="block px-3 py-2 text-xs hover:bg-gray-50">Excel (.xlsx)</a>
                    <a href="{{ route('admin.fabric-records.inspection-report', ['fabric_record' => $fabric_record, 'format' => 'pdf']) }}" class="block px-3 py-2 text-xs hover:bg-gray-50 border-t border-gray-100" target="_blank">PDF</a>
                    <a href="{{ route('admin.fabric-records.inspection-report', ['fabric_record' => $fabric_record, 'format' => 'csv']) }}" class="block px-3 py-2 text-xs hover:bg-gray-50 border-t border-gray-100">CSV</a>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase">
                    <tr>
                        <th class="px-2 py-2 text-left">Roll#</th>
                        <th class="px-2 py-2 text-left">Color</th>
                        <th class="px-2 py-2 text-right">Weight (kg)</th>
                        <th class="px-2 py-2 text-right">Width F/M/E</th>
                        <th class="px-2 py-2 text-right">GSM</th>
                        <th class="px-2 py-2 text-right">Length (yds)</th>
                        <th class="px-2 py-2 text-right">Defects</th>
                        <th class="px-2 py-2 text-right">Pts</th>
                        <th class="px-2 py-2 text-right">Pts/100 sq yd</th>
                        <th class="px-2 py-2 text-center">Result</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($fabric_record->rolls as $roll)
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-2 font-medium">{{ $roll->roll_no }}</td>
                        <td class="px-2 py-2">{{ $roll->color }}</td>
                        <td class="px-2 py-2 text-right">{{ number_format((float)$roll->weight_kgs, 3) }}</td>
                        <td class="px-2 py-2 text-right">{{ $roll->width_front }}/{{ $roll->width_middle }}/{{ $roll->width_end }}</td>
                        <td class="px-2 py-2 text-right">{{ $roll->gsm }}</td>
                        <td class="px-2 py-2 text-right">{{ $roll->roll_length_yards }}</td>
                        <td class="px-2 py-2 text-right">{{ $roll->defects->count() }}</td>
                        <td class="px-2 py-2 text-right">{{ $roll->defects->sum('points') }}</td>
                        <td class="px-2 py-2 text-right">{{ $roll->points_per_100_sq_yd }}</td>
                        <td class="px-2 py-2 text-center"><x-status-badge :status="$roll->result" :label="strtoupper($roll->result)" /></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Defects</h3>
        @if($fabric_record->defects->isNotEmpty())
        <div class="space-y-3">
            @foreach($fabric_record->rolls as $roll)
                @php($rollDefects = $roll->defects)
                @if($rollDefects->isNotEmpty())
                <div class="border border-gray-100 rounded-lg p-3">
                    <div class="text-xs font-medium text-gray-600 mb-2">Roll #{{ $roll->roll_no }}</div>
                    <div class="space-y-2">
                        @foreach($rollDefects as $d)
                        <div class="flex items-start gap-3 text-sm">
                            @if($d->defect_image_path)
                            <img src="{{ asset('storage/' . $d->defect_image_path) }}" alt="Defect" class="w-14 h-14 object-cover rounded border border-gray-200 flex-shrink-0 cursor-pointer" onclick="window.open('{{ asset('storage/' . $d->defect_image_path) }}', '_blank')">
                            @else
                            <div class="w-14 h-14 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-gray-300 flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-800">{{ $d->defect_type }}</span>
                                    <x-status-badge :status="$d->severity" />
                                    @if($d->points)<span class="text-xs text-gray-400">{{ $d->points }} pt</span>@endif
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    @if($d->metre_position)Mtr: {{ $d->metre_position }} · @endif
                                    @if($d->defect_size){{ $d->defect_size }} · @endif
                                    {{ $d->notes }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
            @php($lotDefects = $fabric_record->defects->whereNull('inspection_roll_id'))
            @if($lotDefects->isNotEmpty())
            <div class="border border-gray-100 rounded-lg p-3">
                <div class="text-xs font-medium text-gray-600 mb-2">Lot-level defects</div>
                @foreach($lotDefects as $d)
                <div class="flex items-start gap-3 text-sm mb-2">
                    @if($d->defect_image_path)
                    <img src="{{ asset('storage/' . $d->defect_image_path) }}" alt="Defect" class="w-14 h-14 object-cover rounded border border-gray-200 flex-shrink-0 cursor-pointer" onclick="window.open('{{ asset('storage/' . $d->defect_image_path) }}', '_blank')">
                    @endif
                    <div class="flex-1">
                        <span class="font-medium text-gray-800">{{ $d->defect_type }}</span>
                        <span class="text-xs text-gray-400 ml-2">Count: {{ $d->count }} · {{ $d->notes }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @else
        <p class="text-sm text-gray-400">No defects recorded.</p>
        @endif
    </div>
</div>

@if($fabric_record->alerts->isNotEmpty())
<div class="bg-white rounded-lg shadow-sm p-4">
    <h3 class="text-sm font-semibold text-gray-700 mb-3">Alerts for this Lot</h3>
    <table class="min-w-full text-sm">
        <thead class="text-xs text-gray-500 uppercase"><tr><th class="px-2 py-2 text-left">Type</th><th class="px-2 py-2 text-left">Severity</th><th class="px-2 py-2 text-left">Message</th><th class="px-2 py-2 text-left">Status</th><th class="px-2 py-2 text-left">Date</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($fabric_record->alerts as $a)
            <tr><td class="px-2 py-2"><x-status-badge :status="$a->alert_type" /></td><td class="px-2 py-2"><x-status-badge :status="$a->severity" /></td><td class="px-2 py-2">{{ $a->message }}</td><td class="px-2 py-2">{{ $a->is_resolved ? 'Resolved' : 'Open' }}</td><td class="px-2 py-2 text-xs">{{ $a->created_at->format('Y-m-d') }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
