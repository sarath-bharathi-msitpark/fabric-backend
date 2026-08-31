@extends('layouts.app')

@section('title', 'Edit ' . $fabric_record->lot_no)
@section('header', 'Edit Fabric Record — ' . $fabric_record->lot_no)

@section('actions')
    <a href="{{ route('admin.fabric-records.show', $fabric_record) }}" class="px-3 py-1.5 text-xs rounded-md border border-gray-300 hover:bg-gray-50">Cancel</a>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.fabric-records.update', $fabric_record) }}" x-data="fabricEdit()">
    @csrf @method('PUT')
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Record Fields</h3>
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

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Inspection Details</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Inspected (kg)</label><input type="number" step="0.01" name="inspected_kg" value="{{ old('inspected_kg', $fabric_record->inspection?->inspected_kg) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Approved (kg)</label><input type="number" step="0.01" name="approved_kg" value="{{ old('approved_kg', $fabric_record->inspection?->approved_kg) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Rejected (kg)</label><input type="number" step="0.01" name="rejected_kg" value="{{ old('rejected_kg', $fabric_record->inspection?->rejected_kg) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Pass %</label><input type="number" step="0.01" name="pass_pct" value="{{ old('pass_pct', $fabric_record->inspection?->pass_pct) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">GSM Actual</label><input type="number" step="0.01" name="gsm_actual" value="{{ old('gsm_actual', $fabric_record->inspection?->gsm_actual) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Width Actual</label><input type="number" step="0.01" name="width_actual" value="{{ old('width_actual', $fabric_record->inspection?->width_actual) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Bowing %</label><input type="number" step="0.01" name="bowing_pct" value="{{ old('bowing_pct', $fabric_record->inspection?->bowing_pct) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Skewing %</label><input type="number" step="0.01" name="skewing_pct" value="{{ old('skewing_pct', $fabric_record->inspection?->skewing_pct) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Shade Status</label>
                <select name="shade_status" class="w-full rounded-md border-gray-300 text-sm">
                    @foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(old('shade_status',$fabric_record->inspection?->shade_status)==$s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
            </div>
            <div><label class="block text-xs font-medium text-gray-600 mb-1">Inspection Date</label><input type="date" name="inspection_date" value="{{ old('inspection_date', $fabric_record->inspection?->inspection_date?->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 text-sm"></div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6" x-data="{ defects: {{ json_encode($fabric_record->defects->map(fn($d) => ['defect_type'=>$d->defect_type,'count'=>$d->count,'severity'=>$d->severity,'notes'=>$d->notes])->toArray()) }} }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Defects</h3>
            <button type="button" @click="defects.push({ defect_type:'', count:1, severity:'minor', notes:'' })" class="px-3 py-1 text-xs rounded-md bg-blue-600 text-white hover:bg-blue-700">+ Add Defect Row</button>
        </div>
        <template x-for="(defect, idx) in defects" :key="idx">
            <div class="grid grid-cols-12 gap-2 mb-2 items-center">
                <input type="text" x-model="defect.defect_type" name="defects[idx][defect_type]" placeholder="Defect Type" class="col-span-4 rounded-md border-gray-300 text-sm">
                <input type="number" x-model.number="defect.count" name="defects[idx][count]" placeholder="Count" class="col-span-2 rounded-md border-gray-300 text-sm">
                <select x-model="defect.severity" name="defects[idx][severity]" class="col-span-2 rounded-md border-gray-300 text-sm">
                    <option value="minor">minor</option><option value="major">major</option><option value="critical">critical</option>
                </select>
                <input type="text" x-model="defect.notes" name="defects[idx][notes]" placeholder="Notes" class="col-span-3 rounded-md border-gray-300 text-sm">
                <button type="button" @click="defects.splice(idx,1)" class="col-span-1 text-red-600 hover:text-red-800 text-sm">Remove</button>
            </div>
        </template>
        <p x-show="defects.length === 0" class="text-sm text-gray-400">No defect rows. Click "Add Defect Row" to add one.</p>
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.fabric-records.show', $fabric_record) }}" class="px-4 py-2 text-sm rounded-md border border-gray-300 hover:bg-gray-50">Cancel</a>
        <button type="submit" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Save Changes</button>
    </div>
</form>

@push('scripts')
<script>
function fabricEdit() { return {}; }
</script>
@endpush
@endsection
