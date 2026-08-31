@extends('layouts.app')

@section('title', 'QC Inspection — ' . $fabric_record->lot_no)
@section('header', 'QC Inspection — Lot ' . $fabric_record->lot_no)

@section('actions')
    @if($fabric_record->rolls->isNotEmpty())
    <div class="relative inline-block" x-data="{ open: false }">
        <button @click="open = !open" type="button" class="px-3 py-1.5 text-xs rounded-md bg-green-600 text-white hover:bg-green-700 inline-flex items-center gap-1">
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
    <a href="{{ route('admin.fabric-records.show', $fabric_record) }}" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Cancel</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.fabric-records.update', $fabric_record) }}" x-data="qcInspection()">
    @csrf @method('PUT')

    {{-- Lot info --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Lot Information</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Date</label><input type="date" name="record_date" value="{{ old('record_date', $fabric_record->record_date?->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm" required></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Buyer</label>
                <select name="buyer_id" class="w-full rounded-md border-gray-300 text-sm" required>
                    @foreach($buyers as $b)<option value="{{ $b->id }}" @selected(old('buyer_id',$fabric_record->buyer_id)==$b->id)>{{ $b->buyer_name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Style</label>
                <select name="style_id" class="w-full rounded-md border-gray-300 text-sm" required>
                    @foreach($styles as $s)<option value="{{ $s->id }}" @selected(old('style_id',$fabric_record->style_id)==$s->id)>{{ $s->style_number }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Supplier</label>
                <select name="supplier_id" class="w-full rounded-md border-gray-300 text-sm" required>
                    @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id',$fabric_record->supplier_id)==$s->id)>{{ $s->supplier_name }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Fabric Type</label><input type="text" name="fabric_type" value="{{ old('fabric_type', $fabric_record->fabric_type) }}" class="w-full rounded-md border-gray-300 text-sm" required></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Color</label><input type="text" name="color" value="{{ old('color', $fabric_record->color) }}" class="w-full rounded-md border-gray-300 text-sm" required></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Ordered (kg)</label><input type="number" step="0.01" name="ordered_kg" value="{{ old('ordered_kg', $fabric_record->ordered_kg) }}" class="w-full rounded-md border-gray-300 text-sm" required></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Received (kg)</label><input type="number" step="0.01" name="received_kg" value="{{ old('received_kg', $fabric_record->received_kg) }}" class="w-full rounded-md border-gray-300 text-sm" required></div>
        </div>
    </div>

    {{-- Inspection settings --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Inspection Settings</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div><label class="block text-xs font-medium text-gray-600 mb-1">GSM Target</label><input type="number" step="0.01" name="gsm_target" value="{{ old('gsm_target', $fabric_record->inspection?->gsm_target ?? 220) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Width Target (inches)</label><input type="number" step="0.01" name="width_target" value="{{ old('width_target', $fabric_record->inspection?->width_target ?? 180) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Shade Status</label>
                <select name="shade_status" class="w-full rounded-md border-gray-300 text-sm">
                    @foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(old('shade_status',$fabric_record->inspection?->shade_status)==$s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Inspection Date</label><input type="date" name="inspection_date" value="{{ old('inspection_date', $fabric_record->inspection?->inspection_date?->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        </div>
    </div>

    {{-- Roll inspection --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-700">Roll-by-Roll QC Inspection</h3>
                <p class="text-xs text-gray-500 mt-0.5">Add each roll, then mark defects at metre positions. Points auto-calculate by defect size. Pass/Fail is determined per roll (≤ 20 pts/100 sq yd = PASS).</p>
            </div>
            <button type="button" @click="addRoll()" class="px-3 py-1.5 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add Roll</button>
        </div>

        <template x-for="(roll, ri) in rolls" :key="ri">
            <div class="border border-gray-200 rounded-lg p-4 mb-4" :class="roll.result === 'fail' ? 'border-red-300 bg-red-50' : (roll.result === 'pass' ? 'border-green-300 bg-green-50' : '')">
                {{-- Roll header --}}
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-gray-700">Roll #<span x-text="roll.roll_no"></span></span>
                    <div class="flex items-center gap-3">
                        <span class="text-xs px-2 py-0.5 rounded font-medium"
                              :class="roll.result === 'pass' ? 'bg-green-200 text-green-800' : (roll.result === 'fail' ? 'bg-red-200 text-red-800' : 'bg-gray-200 text-gray-600')"
                              x-show="roll.result" x-text="roll.result === 'pass' ? 'PASS' : 'FAIL'"></span>
                        <span class="text-xs text-gray-500" x-show="roll.points_per_100_sq_yd !== null">
                            <span x-text="roll.points_per_100_sq_yd"></span> pts/100 sq yd
                        </span>
                        <button type="button" @click="rolls.splice(ri, 1)" class="text-red-600 hover:text-red-800 text-xs">Remove Roll</button>
                    </div>
                </div>

                {{-- Roll measurements --}}
                <div class="grid grid-cols-3 md:grid-cols-7 gap-2 mb-3">
                    <input type="number" x-model.number="roll.roll_no" :name="`rolls[${ri}][roll_no]`" placeholder="Roll #" class="rounded-md border-gray-300 text-sm" required>
                    <input type="text" x-model="roll.color" :name="`rolls[${ri}][color]`" placeholder="Color" class="rounded-md border-gray-300 text-sm">
                    <input type="number" step="0.001" x-model.number="roll.weight_kgs" @input="recalcRoll(roll)" :name="`rolls[${ri}][weight_kgs]`" placeholder="Weight (kg)" class="rounded-md border-gray-300 text-sm" required>
                    <input type="number" step="0.1" x-model.number="roll.width_front" @input="recalcRoll(roll)" :name="`rolls[${ri}][width_front]`" placeholder="Width F" class="rounded-md border-gray-300 text-sm">
                    <input type="number" step="0.1" x-model.number="roll.width_middle" @input="recalcRoll(roll)" :name="`rolls[${ri}][width_middle]`" placeholder="Width M" class="rounded-md border-gray-300 text-sm">
                    <input type="number" step="0.1" x-model.number="roll.width_end" @input="recalcRoll(roll)" :name="`rolls[${ri}][width_end]`" placeholder="Width E" class="rounded-md border-gray-300 text-sm">
                    <input type="number" step="0.1" x-model.number="roll.gsm" @input="recalcRoll(roll)" :name="`rolls[${ri}][gsm]`" placeholder="GSM" class="rounded-md border-gray-300 text-sm">
                </div>

                {{-- Calculated fields --}}
                <div class="grid grid-cols-3 gap-2 mb-3 text-xs">
                    <div class="bg-gray-50 rounded px-2 py-1.5">
                        <span class="text-gray-500">Roll Length:</span>
                        <span class="font-medium text-gray-700" x-text="roll.roll_length_yards ? roll.roll_length_yards + ' yds' : '—'"></span>
                    </div>
                    <div class="bg-gray-50 rounded px-2 py-1.5">
                        <span class="text-gray-500">Total Points:</span>
                        <span class="font-medium text-gray-700" x-text="rollTotalPoints(roll)"></span>
                    </div>
                    <div class="bg-gray-50 rounded px-2 py-1.5">
                        <span class="text-gray-500">Avg Width:</span>
                        <span class="font-medium text-gray-700" x-text="rollAvgWidth(roll) + '"'"></span>
                    </div>
                </div>

                {{-- Defect rows --}}
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-600">Defects for this roll</span>
                        <button type="button" @click="addDefect(roll)" class="text-xs text-blue-600 hover:text-blue-800">+ Add Defect</button>
                    </div>
                    <template x-for="(defect, di) in roll.defects" :key="di">
                        <div class="grid grid-cols-12 gap-2 mb-2 items-center">
                            <input type="number" x-model.number="defect.metre_position" :name="`rolls[${ri}][defects][${di}][metre_position]`" placeholder="Mtr" class="col-span-1 rounded-md border-gray-300 text-sm">
                            <select x-model="defect.defect_type" :name="`rolls[${ri}][defects][${di}][defect_type]`" class="col-span-3 rounded-md border-gray-300 text-sm">
                                <option value="">Defect type...</option>
                                @foreach($defectTypes as $dt)<option value="{{ $dt }}">{{ $dt }}</option>@endforeach
                            </select>
                            <select x-model="defect.defect_size" @change="onSizeChange(defect, roll)" :name="`rolls[${ri}][defects][${di}][defect_size]`" class="col-span-4 rounded-md border-gray-300 text-sm">
                                <option value="">Defect size (auto-points)...</option>
                                @foreach($defectSizeOptions as $size => $pts)<option value="{{ $size }}">{{ $size }} ({{ $pts }} pt)</option>@endforeach
                            </select>
                            <input type="number" x-model.number="defect.points" @input="recalcRoll(roll)" :name="`rolls[${ri}][defects][${di}][points]`" placeholder="Pts" min="1" max="4" class="col-span-1 rounded-md border-gray-300 text-sm">
                            <input type="text" x-model="defect.notes" :name="`rolls[${ri}][defects][${di}][notes]`" placeholder="Notes" class="col-span-2 rounded-md border-gray-300 text-sm">
                            <button type="button" @click="roll.defects.splice(di, 1); recalcRoll(roll)" class="col-span-1 text-red-600 hover:text-red-800 text-sm">Remove</button>
                        </div>
                    </template>
                    <p x-show="roll.defects.length === 0" class="text-xs text-gray-400">No defects for this roll. Click "+ Add Defect" to mark one.</p>
                </div>

                <input type="hidden" :name="`rolls[${ri}][remarks]`" :value="roll.remarks ?? ''">
            </div>
        </template>

        <p x-show="rolls.length === 0" class="text-sm text-gray-400">No rolls added yet. Click "+ Add Roll" to start inspecting.</p>
    </div>

    {{-- Auto-calculated summary --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Inspection Summary (auto-calculated from rolls)</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-blue-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Inspected Weight</div>
                <div class="text-lg font-bold text-blue-700" x-text="summary.inspectedKg + ' kg'"></div>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Passed Qty</div>
                <div class="text-lg font-bold text-green-700" x-text="summary.passedKg + ' kg'"></div>
            </div>
            <div class="bg-red-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Failed Qty</div>
                <div class="text-lg font-bold text-red-700" x-text="summary.failedKg + ' kg'"></div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-500">Pass %</div>
                <div class="text-lg font-bold text-gray-700" x-text="summary.passPct + ' %'"></div>
            </div>
            <div class="rounded-lg p-3" :class="summary.overallResult === 'PASS' ? 'bg-green-100' : 'bg-red-100'">
                <div class="text-xs text-gray-500">Overall Result</div>
                <div class="text-lg font-bold" :class="summary.overallResult === 'PASS' ? 'text-green-700' : 'text-red-700'" x-text="summary.overallResult"></div>
                <div class="text-xs text-gray-500" x-text="summary.overallPoints + ' pts/100 sq yd'"></div>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.fabric-records.show', $fabric_record) }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save Inspection</button>
    </div>
</form>

@push('scripts')
@php
    $existingRollsJson = $fabric_record->rolls->map(function ($r) {
        return [
            'roll_no' => $r->roll_no,
            'color' => $r->color,
            'weight_kgs' => (float) $r->weight_kgs,
            'width_front' => (float) $r->width_front,
            'width_middle' => (float) $r->width_middle,
            'width_end' => (float) $r->width_end,
            'gsm' => (float) $r->gsm,
            'roll_length_yards' => (float) $r->roll_length_yards,
            'points_per_100_sq_yd' => (float) $r->points_per_100_sq_yd,
            'result' => $r->result,
            'remarks' => $r->remarks,
            'defects' => $r->defects->map(function ($d) {
                return [
                    'defect_type' => $d->defect_type,
                    'metre_position' => $d->metre_position,
                    'points' => $d->points,
                    'defect_size' => $d->defect_size,
                    'notes' => $d->notes,
                ];
            })->toArray(),
        ];
    })->toJson();
@endphp
<script>
const defectSizePoints = {{ Illuminate\Support\Js::from($defectSizeOptions) }};
const existingRolls = {!! $existingRollsJson !!};

function qcInspection() {
    return {
        rolls: existingRolls.length ? existingRolls : [],
        summary: { inspectedKg: 0, passedKg: 0, failedKg: 0, passPct: 0, overallPoints: 0, overallResult: '—' },

        init() { this.recalcAll(); },

        addRoll() {
            const nextNo = this.rolls.length > 0 ? Math.max(...this.rolls.map(r => r.roll_no || 0)) + 1 : 1;
            this.rolls.push({
                roll_no: nextNo, color: '', weight_kgs: null,
                width_front: null, width_middle: null, width_end: null, gsm: null,
                roll_length_yards: null, points_per_100_sq_yd: null, result: '',
                remarks: '', defects: [],
            });
        },

        addDefect(roll) {
            roll.defects.push({ defect_type: '', metre_position: null, points: null, defect_size: '', notes: '' });
        },

        onSizeChange(defect, roll) {
            if (defect.defect_size && defectSizePoints[defect.defect_size] !== undefined) {
                defect.points = defectSizePoints[defect.defect_size];
            }
            this.$nextTick(() => this.recalcRoll(roll));
        },

        rollAvgWidth(roll) {
            const vals = [roll.width_front, roll.width_middle, roll.width_end].filter(v => v > 0);
            return vals.length ? (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(2) : '0';
        },

        rollTotalPoints(roll) {
            return roll.defects.reduce((sum, d) => sum + (parseInt(d.points) || 0), 0);
        },

        recalcRoll(roll) {
            const width = parseFloat(this.rollAvgWidth(roll));
            const gsm = parseFloat(roll.gsm) || 0;
            const weight = parseFloat(roll.weight_kgs) || 0;

            if (gsm > 0 && width > 0 && weight > 0) {
                roll.roll_length_yards = ((weight * 1000 * 100 * 1.0936) / (gsm * width * 2.54 * 0.9144)).toFixed(1);
            } else {
                roll.roll_length_yards = null;
            }

            const totalPoints = this.rollTotalPoints(roll);
            const yards = parseFloat(roll.roll_length_yards) || 0;
            if (yards > 0 && width > 0) {
                roll.points_per_100_sq_yd = ((totalPoints * 3600) / (yards * width)).toFixed(1);
            } else {
                roll.points_per_100_sq_yd = null;
            }

            roll.result = (parseFloat(roll.points_per_100_sq_yd) || 0) <= 20 ? 'pass' : 'fail';
            this.recalcSummary();
        },

        recalcAll() {
            this.rolls.forEach(r => this.recalcRoll(r));
        },

        recalcSummary() {
            let inspected = 0, passed = 0, failed = 0, totalPoints = 0, totalYards = 0, widthSum = 0;
            this.rolls.forEach(roll => {
                const w = parseFloat(roll.weight_kgs) || 0;
                inspected += w;
                if (roll.result === 'pass') passed += w;
                else if (roll.result === 'fail') failed += w;
                totalPoints += this.rollTotalPoints(roll);
                totalYards += parseFloat(roll.roll_length_yards) || 0;
                widthSum += parseFloat(this.rollAvgWidth(roll));
            });
            const avgWidth = this.rolls.length ? widthSum / this.rolls.length : 0;
            const overallPoints = (totalYards > 0 && avgWidth > 0) ? ((totalPoints * 3600) / (totalYards * avgWidth)).toFixed(1) : 0;
            const passPct = inspected > 0 ? ((passed / inspected) * 100).toFixed(2) : 0;
            const overallResult = (parseFloat(overallPoints) || 0) > 18 ? 'FAIL' : (this.rolls.length > 0 ? 'PASS' : '—');

            this.summary = {
                inspectedKg: inspected.toFixed(2),
                passedKg: passed.toFixed(2),
                failedKg: failed.toFixed(2),
                passPct: passPct,
                overallPoints: overallPoints,
                overallResult: overallResult,
            };
        },
    };
}
</script>
@endpush
@endsection
