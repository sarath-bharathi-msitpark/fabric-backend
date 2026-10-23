@extends('layouts.app')

@section('title', ($audit->audit_no ?? 'Audit') . ' · ' . \App\Models\CuttingAudit::STAGE_LABELS[$stage])
@section('header', \App\Models\CuttingAudit::STAGE_LABELS[$stage] . ' — ' . $audit->audit_no)

@section('actions')
    <a href="{{ route('admin.cutting.audits.show', $audit) }}" class="px-3 py-2 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Back to audit</a>
    <a href="{{ route('admin.cutting.audits.report', $audit) }}" class="px-3 py-2 text-xs rounded-md bg-slate-700 text-white hover:bg-slate-800">PDF</a>
@endsection

@php
    $checked = collect($checkpoints)->whereNotNull('result')->count();
    $total = count($checkpoints);
    $locked = $stageModel && $stageModel->isLocked();
    $isBundling = $stage === 'bundling';
    $isCutPanel = $stage === 'cut_panel';
@endphp

@section('content')
<div class="space-y-4">

    {{-- Workflow progress --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            @foreach(\App\Models\CuttingAudit::STAGES as $i => $s)
                @php $sm = $audit->stages->firstWhere('stage', $s); @endphp
                <div class="flex-1 flex items-center gap-2">
                    <span class="w-7 h-7 flex items-center justify-center rounded-full text-xs font-semibold
                        {{ $sm && in_array($sm->status, ['approved','special_approved']) ? 'bg-green-500 text-white' : ($sm && $sm->status === 'not_approved' ? 'bg-red-500 text-white' : ($s === $stage ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600')) }}">
                        {{ $i + 1 }}
                    </span>
                    <div class="min-w-0">
                        <div class="text-xs font-medium text-gray-800 truncate">{{ \App\Models\CuttingAudit::STAGE_LABELS[$s] }}</div>
                        <div class="text-[10px] text-gray-500">{{ $sm ? ucfirst(str_replace('_',' ', $sm->status)) : 'Not started' }}</div>
                    </div>
                    @if(!$loop->last)<div class="hidden sm:block flex-1 h-px bg-gray-200"></div>@endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Order header + lay summary --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div><span class="block text-[10px] uppercase text-gray-400">Table</span>{{ $audit->cuttingTable?->table_no ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Date</span>{{ $audit->audit_date?->format('d M Y') }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Buyer</span>{{ $audit->buyer?->buyer_name ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Shift</span>{{ $audit->shift ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Style#</span>{{ $audit->style_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">PO#</span>{{ $audit->po_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">LOT#</span>{{ $audit->lot_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Lay# / Cut#</span>{{ $audit->lay_number ?? '—' }} / {{ $audit->cut_number ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Sizes</span>{{ $audit->number_of_sizes }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Lay method</span>{{ ucfirst($audit->lay_method) }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Plies (req/act)</span>{{ $audit->required_plies ?? '—' }} / {{ $audit->actual_plies ?? '—' }}</div>
            <div><span class="block text-[10px] uppercase text-gray-400">Revision</span>{{ $audit->revision }}</div>
        </div>
        @if($audit->actualLengthOutOfTolerance() || $audit->actualWidthOutOfTolerance() || $audit->pliesMismatch())
            <div class="mt-3 rounded-md bg-yellow-50 border border-yellow-200 text-yellow-800 px-3 py-2 text-xs">
                Tolerance flags:
                @if($audit->actualLengthOutOfTolerance()) Length outside tolerance. @endif
                @if($audit->actualWidthOutOfTolerance()) Width outside tolerance. @endif
                @if($audit->pliesMismatch()) Actual plies differ from required. @endif
            </div>
        @endif
    </div>

    @if(!$unlocked)
        <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
            <p class="font-medium text-gray-700">This stage is locked.</p>
            <p class="text-sm mt-1">It unlocks once the previous stage is signed off with an approved or special-approval result.</p>
        </div>
    @else
        <form method="POST" action="{{ $locked ? '#' : route('admin.cutting.stages.draft', [$audit, $stage]) }}" enctype="multipart/form-data" id="stage-form">
            @csrf

            {{-- Checkpoints --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 text-sm">Checklist</h3>
                    <span class="text-xs text-gray-500">Progress: <strong>{{ $checked }} of {{ $total }}</strong> checked</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2 text-left w-10">#</th>
                                <th class="px-3 py-2 text-left">Checkpoint</th>
                                <th class="px-3 py-2 text-left w-40">Result</th>
                                <th class="px-3 py-2 text-left">Remarks</th>
                                <th class="px-3 py-2 text-left w-48">Photo (Not OK)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($checkpoints as $i => $cp)
                            <tr class="align-top">
                                <td class="px-3 py-2 text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-800">{{ $cp['checkpoint_name'] }}</div>
                                    @if($cp['description'] ?? null)<div class="text-[11px] text-gray-500">{{ $cp['description'] }}</div>@endif
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @if($cp['mandatory_remarks_on_not_ok'] ?? false)<span class="text-[9px] px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">Remarks required on Not OK</span>@endif
                                        @if($cp['blocks_approval'] ?? false)<span class="text-[9px] px-1.5 py-0.5 rounded bg-red-100 text-red-700">Blocks approval</span>@endif
                                    </div>
                                    <input type="hidden" name="checkpoints[{{ $i }}][checkpoint_id]" value="{{ $cp['checkpoint_id'] }}">
                                    <input type="hidden" name="checkpoints[{{ $i }}][checkpoint_name]" value="{{ $cp['checkpoint_name'] }}">
                                    <input type="hidden" name="checkpoints[{{ $i }}][mandatory_remarks_on_not_ok]" value="{{ ($cp['mandatory_remarks_on_not_ok'] ?? false) ? 1 : 0 }}">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-3">
                                        <label class="inline-flex items-center gap-1 text-xs"><input type="radio" name="checkpoints[{{ $i }}][result]" value="ok" @checked($cp['result']==='ok') @disabled($locked)> OK</label>
                                        <label class="inline-flex items-center gap-1 text-xs"><input type="radio" name="checkpoints[{{ $i }}][result]" value="not_ok" @checked($cp['result']==='not_ok') @disabled($locked)> Not OK</label>
                                    </div>
                                </td>
                                <td class="px-3 py-2"><textarea name="checkpoints[{{ $i }}][remarks]" rows="1" @disabled($locked) class="w-full rounded-md border-gray-300 text-xs">{{ $cp['remarks'] }}</textarea></td>
                                <td class="px-3 py-2">
                                    @if(!$locked)
                                        <input type="file" name="checkpoints[{{ $i }}][photos][]" multiple accept="image/*" class="w-full text-[10px]">
                                    @endif
                                    @foreach($cp['photos'] as $p)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($p) }}" target="_blank" class="inline-block text-[10px] text-blue-600 underline mr-1">photo</a>
                                    @endforeach
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Size-wise pattern matching (cut panel) --}}
            @if($isCutPanel)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4">
                <div class="px-4 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Size-wise Pattern Matching</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2 text-left">Size</th>
                                <th class="px-3 py-2 text-center">Top</th>
                                <th class="px-3 py-2 text-center">Middle</th>
                                <th class="px-3 py-2 text-center">Bottom</th>
                                <th class="px-3 py-2 text-left">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($sizeRows as $i => $row)
                            <tr>
                                <td class="px-3 py-2"><input type="text" name="size_patterns[{{ $i }}][size_name]" value="{{ $row['size_name'] }}" @disabled($locked) placeholder="Size {{ $i+1 }}" class="w-24 rounded-md border-gray-300 text-xs"></td>
                                @foreach(['top_result','middle_result','bottom_result'] as $pos)
                                <td class="px-3 py-2 text-center">
                                    <select name="size_patterns[{{ $i }}][{{ $pos }}]" @disabled($locked) class="rounded-md border-gray-300 text-xs">
                                        <option value="">—</option>
                                        <option value="1" @selected($row[$pos] === true)>Match</option>
                                        <option value="0" @selected($row[$pos] === false)>No match</option>
                                    </select>
                                </td>
                                @endforeach
                                <td class="px-3 py-2"><input type="text" name="size_patterns[{{ $i }}][remarks]" value="{{ $row['remarks'] }}" @disabled($locked) class="w-full rounded-md border-gray-300 text-xs"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Bundles (bundling) --}}
            @if($isBundling)
            @php $hasSavedBundles = $audit->bundles->isNotEmpty(); @endphp
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4" x-data="bundleRows({{ $audit->bundles->count() ?: 1 }})">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800 text-sm">Bundles</h3>
                    @if(!$locked)<button type="button" @click="add()" class="text-xs px-2 py-1 rounded bg-blue-600 text-white">+ Add bundle</button>@endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2 text-left">Bundle No</th>
                                <th class="px-3 py-2 text-left">Size</th>
                                <th class="px-3 py-2 text-left">Panel Numbers</th>
                                <th class="px-3 py-2 text-left w-24">Pieces</th>
                                @if($hasSavedBundles)<th class="px-3 py-2 text-left">Print</th>@endif
                                @if(!$locked)<th class="px-3 py-2 text-right w-12"></th>@endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(row, index) in rows" :key="row.key">
                                <tr>
                                    <td class="px-3 py-2"><input :name="'bundles['+index+'][bundle_no]'" x-model="row.bundle_no" @disabled($locked) placeholder="B-0001" class="w-28 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input :name="'bundles['+index+'][size]'" x-model="row.size" @disabled($locked) placeholder="M" class="w-20 rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input :name="'bundles['+index+'][panel_numbers]'" x-model="row.panel_numbers" @disabled($locked) placeholder="1-25" class="w-full rounded-md border-gray-300 text-xs"></td>
                                    <td class="px-3 py-2"><input type="number" min="1" :name="'bundles['+index+'][pieces]'" x-model="row.pieces" @disabled($locked) class="w-20 rounded-md border-gray-300 text-xs"></td>
                                    @if($hasSavedBundles)
                                    <td class="px-3 py-2">
                                        <template x-if="row.id">
                                            <a :href="'/admin/cutting/bundles/'+row.id+'/print'" target="_blank" class="text-[10px] text-blue-600 underline">Card</a>
                                        </template>
                                    </td>
                                    @endif
                                    @if(!$locked)
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" @click="remove(index)" title="Remove bundle" aria-label="Remove bundle" class="text-red-500 hover:text-red-700 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                    @endif
                                </tr>
                            </template>
                            <template x-if="rows.length === 0">
                                <tr><td colspan="6" class="px-3 py-6 text-center text-gray-400 text-xs">No bundles added. Click &ldquo;+ Add bundle&rdquo;.</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Overall result --}}
            <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Overall result {{ $locked ? '' : '*' }}</label>
                        <select name="result" @disabled($locked) class="w-full rounded-md border-gray-300 text-sm">
                            <option value="">— Select —</option>
                            @foreach($resultOptions as $key => $label)
                                <option value="{{ $key }}" @selected($stageModel?->result === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Inspection date &amp; time</label>
                        <input type="datetime-local" name="inspection_date" value="{{ optional($stageModel?->inspection_date)->format('Y-m-d\TH:i') }}" @disabled($locked) class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Comments</label>
                        <textarea name="comments" rows="2" @disabled($locked) class="w-full rounded-md border-gray-300 text-sm">{{ $stageModel?->comments }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Special approval reason (required when selecting special approval)</label>
                        <input type="text" name="special_reason" @disabled($locked) class="w-full rounded-md border-gray-300 text-sm">
                    </div>
                </div>
            </div>

            @if(!$locked)
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 mb-6">
                <button type="submit" formaction="{{ route('admin.cutting.stages.draft', [$audit, $stage]) }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Save draft</button>
                <button type="submit" formaction="{{ route('admin.cutting.stages.submit', [$audit, $stage]) }}" onclick="return confirm('Submit and lock this stage? It cannot be edited afterwards without a revision.')" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Submit stage</button>
            </div>
            @endif
        </form>
    @endif

    {{-- Sign-offs --}}
    @if($stageModel && $stageModel->signoffs->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-4">
        <div class="px-4 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Digital Sign-offs</h3></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px]"><tr><th class="px-3 py-2 text-left">Role</th><th class="px-3 py-2 text-left">Signer</th><th class="px-3 py-2 text-left">Result</th><th class="px-3 py-2 text-left">Comments</th><th class="px-3 py-2 text-left">Signed At</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($stageModel->signoffs as $s)
                    <tr>
                        <td class="px-3 py-2">{{ ucwords(str_replace('_',' ', $s->role)) }}</td>
                        <td class="px-3 py-2">{{ $s->user_name }}</td>
                        <td class="px-3 py-2">{{ $s->result ? ucfirst(str_replace('_',' ', $s->result)) : '—' }}</td>
                        <td class="px-3 py-2 text-xs">{{ $s->comments ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs">{{ $s->signed_at?->format('d M Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Awaiting sign-off --}}
    @if($stageModel && in_array($stageModel->status, ['submitted','special_pending']) && $nextRole)
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <h3 class="font-semibold text-gray-800 text-sm mb-2">Awaiting sign-off: <span class="text-blue-700">{{ ucwords(str_replace('_',' ', $nextRole)) }}</span></h3>
        @can('signoff', [$audit, $stage])
            @if($stageModel->signoffs->contains('user_id', auth()->id()))
                <p class="text-xs text-gray-500">You have already signed off this stage.</p>
            @else
            <form method="POST" action="{{ route('admin.cutting.signoffs.store', [$audit, $stage]) }}" class="space-y-2">@csrf
                <textarea name="comments" rows="2" placeholder="Comments" class="w-full rounded-md border-gray-300 text-sm"></textarea>
                <button class="px-4 py-2 text-sm rounded-md bg-green-600 text-white hover:bg-green-700">Sign off</button>
            </form>
            @endif
        @else
            <p class="text-xs text-gray-500">You are not authorized to sign off this stage.</p>
        @endcan
    </div>
    @endif

    {{-- Special approvals --}}
    @if($audit->specialApprovals->isNotEmpty())
    <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-100"><h3 class="font-semibold text-gray-800 text-sm">Special Approvals</h3></div>
        <div class="divide-y divide-gray-100">
            @foreach($audit->specialApprovals as $approval)
            <div class="p-4 text-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $approval->status === 'approved' ? 'bg-green-100 text-green-700' : ($approval->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ ucfirst($approval->status) }}</span>
                    <span class="text-xs text-gray-500">{{ \App\Models\CuttingAudit::STAGE_LABELS[$approval->stage] ?? $approval->stage }}</span>
                </div>
                <p class="mt-1 text-gray-700"><strong>Reason:</strong> {{ $approval->reason }}</p>
                <p class="text-xs text-gray-500">Requested by {{ $approval->requester?->name }} · {{ $approval->created_at?->format('d M Y H:i') }}</p>
                @if($approval->status === 'pending' && auth()->user()->can('approve special approval'))
                <form method="POST" action="{{ route('admin.cutting.approvals.decide', $approval) }}" class="mt-2 flex flex-col sm:flex-row gap-2">@csrf
                    <input type="text" name="decision_reason" placeholder="Decision reason" class="flex-1 rounded-md border-gray-300 text-xs">
                    <button name="decision" value="approve" class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white">Approve</button>
                    <button name="decision" value="reject" class="px-3 py-1.5 text-xs rounded-md bg-red-600 text-white">Reject</button>
                </form>
                @elseif($approval->approved_at)
                <p class="text-xs text-gray-500">Decided by {{ $approval->approver?->name }} · {{ $approval->approved_at?->format('d M Y H:i') }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function bundleRows(count) {
    return {
        rows: [],
        init() {
            const existing = {!! json_encode($audit->bundles->map(fn ($b) => [
                'key' => uniqid(), 'id' => $b->id, 'bundle_no' => $b->bundle_no,
                'size' => $b->size, 'panel_numbers' => $b->panel_numbers, 'pieces' => $b->pieces,
            ])->values()) !!};
            if (existing.length) {
                this.rows = existing;
            } else {
                for (let i = 0; i < count; i++) this.add();
            }
        },
        add() { this.rows.push({ key: Math.random().toString(36).slice(2), id: null, bundle_no: '', size: '', panel_numbers: '', pieces: '' }); },
        remove(index) { this.rows.splice(index, 1); }
    };
}
</script>
@endpush
