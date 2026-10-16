@extends('layouts.app')

@section('title', 'Audit ' . $audit->audit_no)
@section('header', 'Cutting Audit ' . $audit->audit_no)

@section('actions')
    @can('update', $audit)
        <a href="{{ route('admin.cutting.audits.edit', $audit) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Edit header</a>
    @endcan
    <a href="{{ route('admin.cutting.audits.report', $audit) }}" class="px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">PDF</a>
    <a href="{{ route('admin.cutting.audits.index') }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Register</a>
@endsection

@section('content')
<div class="space-y-4">

    <div class="flex flex-wrap items-center gap-2">
        <x-status-badge :status="$audit->status === 'closed' ? 'completed' : ($audit->status === 'draft' ? 'pending' : 'in_progress')" :label="ucfirst($audit->status)" />
        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">Revision {{ $audit->revision }}</span>
        <span class="text-xs text-gray-500">Current stage: {{ \App\Models\CuttingAudit::STAGE_LABELS[$audit->current_stage] ?? ucfirst($audit->current_stage) }}</span>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div><span class="block text-[10px] uppercase text-gray-400">Table</span>{{ $audit->cuttingTable?->table_no ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Date</span>{{ $audit->audit_date?->format('d M Y') }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Shift</span>{{ $audit->shift ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Buyer</span>{{ $audit->buyer?->buyer_name ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Style#</span>{{ $audit->style_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Style name</span>{{ $audit->style_name ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">PO#</span>{{ $audit->po_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">LOT#</span>{{ $audit->lot_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Fabric</span>{{ $audit->fabric ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Colour</span>{{ $audit->colour ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">DIA</span>{{ $audit->dia ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Created by</span>{{ $audit->creator?->name ?? '—' }}</div>
        </div>
    </div>

    {{-- Lay details --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 text-sm mb-3">Lay Details</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div><span class="block text-[10px] uppercase text-gray-400">Lay#</span>{{ $audit->lay_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Cut#</span>{{ $audit->cut_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Sizes</span>{{ $audit->number_of_sizes }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Lay method</span>{{ ucfirst($audit->lay_method) }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Marking type</span>{{ $audit->marking_type ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Req L×W (m)</span>{{ $audit->required_lay_length ?? '—' }} × {{ $audit->required_lay_width ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Act L×W (m)</span>{{ $audit->actual_lay_length ?? '—' }} × {{ $audit->actual_lay_width ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Plies req/act</span>{{ $audit->required_plies ?? '—' }} / {{ $audit->actual_plies ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Lay height (cm)</span>{{ $audit->actual_lay_height ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Total parts</span>{{ $audit->total_parts ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Tolerance %</span>{{ $audit->tolerance_pct }}</div>
        </div>
    </div>

    {{-- Stage cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @foreach(\App\Models\CuttingAudit::STAGES as $stage)
            @php
                $sm = $audit->stages->firstWhere('stage', $stage);
                $unlocked = $workflow->isStageUnlocked($audit, $stage);
            @endphp
            <div class="bg-white rounded-lg shadow-sm p-4 flex flex-col">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-gray-800 text-sm">{{ \App\Models\CuttingAudit::STAGE_LABELS[$stage] }}</h3>
                    <x-status-badge :status="$sm ? ($sm->status === 'approved' ? 'approved' : ($sm->status === 'special_approved' ? 'approved' : ($sm->status === 'not_approved' ? 'rejected' : 'pending'))) : 'pending'" :label="$sm ? ucfirst(str_replace('_',' ', $sm->status)) : 'Not started'" />
                </div>
                @if($sm?->result)
                    <p class="text-xs text-gray-600 mb-1"><strong>Result:</strong> {{ $sm->resultLabel() }}</p>
                @endif
                <p class="text-xs text-gray-500 mb-3">
                    {{ $sm?->inspection_date?->format('d M Y H:i') ?? 'No inspection yet' }}
                </p>
                <div class="mt-auto flex flex-wrap gap-2">
                    @if($unlocked || $sm)
                        <a href="{{ route('admin.cutting.stages.show', [$audit, $stage]) }}" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">{{ $sm ? 'View' : 'Inspect' }}</a>
                    @else
                        <span class="text-xs text-gray-400">Locked</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Bundles --}}
    @if($audit->bundles->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800 text-sm">Bundles</h3>
            @php $bundling = $audit->stages->firstWhere('stage','bundling'); @endphp
            @can('release', $audit)
                @if($bundling && in_array($bundling->status, ['approved','special_approved']) && $audit->status !== 'closed')
                    <form method="POST" action="{{ route('admin.cutting.audits.release', $audit) }}" onsubmit="return confirm('Release all bundles to sewing and close this audit?')">@csrf
                        <button class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white hover:bg-green-700">Release to sewing</button>
                    </form>
                @endif
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]"><tr><th class="px-3 py-2 text-left">Bundle No</th><th class="px-3 py-2 text-left">Size</th><th class="px-3 py-2 text-left">Panels</th><th class="px-3 py-2 text-left">Pieces</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Card</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($audit->bundles as $bundle)
                    <tr>
                        <td class="px-3 py-2 font-medium">{{ $bundle->bundle_no }}</td>
                        <td class="px-3 py-2">{{ $bundle->size ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs">{{ $bundle->panel_numbers ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $bundle->pieces }}</td>
                        <td class="px-3 py-2"><x-status-badge :status="$bundle->status === 'released' ? 'approved' : 'pending'" :label="ucfirst($bundle->status)" /></td>
                        <td class="px-3 py-2"><a href="{{ route('admin.cutting.bundles.print', $bundle) }}" target="_blank" class="text-xs text-blue-600 underline">Print</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Revision history --}}
    @if($revisions->count() > 1)
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Revision History</h3></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]"><tr><th class="px-3 py-2 text-left">Revision</th><th class="px-3 py-2 text-left">Audit ID</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Created</th><th class="px-3 py-2 text-left"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($revisions as $rev)
                    <tr class="{{ $rev->id === $audit->id ? 'bg-blue-50' : '' }}">
                        <td class="px-3 py-2">{{ $rev->revision }}</td>
                        <td class="px-3 py-2">{{ $rev->audit_no }}</td>
                        <td class="px-3 py-2">{{ ucfirst($rev->status) }}</td>
                        <td class="px-3 py-2 text-xs">{{ $rev->created_at?->format('d M Y H:i') }}</td>
                        <td class="px-3 py-2">@if($rev->id !== $audit->id)<a href="{{ route('admin.cutting.audits.show', $rev) }}" class="text-xs text-blue-600 underline">Open</a>@else <span class="text-xs text-gray-400">Current</span>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Revision request for rejected stages --}}
    @php $rejected = $audit->stages->first(fn($s) => in_array($s->status, ['not_approved'])); @endphp
    @if($rejected && $audit->status !== 'closed')
    @can('update', $audit)
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-gray-800 text-sm mb-2">Re-inspection</h3>
        <p class="text-xs text-gray-500 mb-2">Create a new revision for correction and reinspection. The previous revision and its history are preserved.</p>
        <form method="POST" action="{{ route('admin.cutting.audits.revision', $audit) }}" class="flex flex-col sm:flex-row gap-2">@csrf
            <input type="hidden" name="stage" value="{{ $rejected->stage }}">
            <input type="text" name="reason" placeholder="Reason for re-inspection" class="flex-1 rounded-md border-gray-300 text-sm">
            <button class="px-4 py-2 text-sm rounded-md bg-orange-600 text-white hover:bg-orange-700">Create revision</button>
        </form>
    </div>
    @endcan
    @endif

</div>
@endsection
